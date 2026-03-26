(function (plugins, editPost, element, components, data, blocks) {
	const { registerPlugin } = plugins;
	const { PluginDocumentSettingPanel } = editPost;
	const el = element.createElement;
	const { Button, PanelRow } = components;
	const { useSelect } = data;
	const { createBlock, cloneBlock } = blocks;

	// ---------------------------------------------------------------------------
	// Convert to Sections
	// ---------------------------------------------------------------------------

	/**
	 * Scan top-level blocks, group H2 headings and the content that follows
	 * each one into CTLawHelp Section blocks, and replace the post content.
	 *
	 * Content before the first H2 is kept as intro blocks outside any section.
	 * Only top-level H2 blocks trigger new sections; other heading levels are
	 * treated as regular content inside the current section.
	 */
	function convertToSections() {
		const allBlocks = data.select('core/block-editor').getBlocks();

		// Guard: bail if Section blocks already exist to avoid double-wrapping.
		if ( allBlocks.some(function (b) { return b.name === 'ctlh/section-prototype'; }) ) {
			window.alert('This post already contains Section blocks.\nRemove them before running Convert to Sections.');
			return;
		}

		// Guard: nothing to convert.
		const hasH2 = allBlocks.some(function (b) {
			return b.name === 'core/heading' && b.attributes.level === 2;
		});
		if ( ! hasH2 ) {
			window.alert('No H2 headings found. Add H2 headings to define section boundaries.');
			return;
		}

		var introBlocks = [];
		var sections   = [];
		var current    = null;

		allBlocks.forEach(function (block) {
			var isH2 = block.name === 'core/heading' && block.attributes.level === 2;

			if ( isH2 ) {
				if ( current !== null ) {
					sections.push( createBlock('ctlh/section-prototype', {}, current) );
				}
				current = [ cloneBlock(block) ];
			} else if ( current === null ) {
				introBlocks.push( cloneBlock(block) );
			} else {
				current.push( cloneBlock(block) );
			}
		});

		if ( current !== null ) {
			sections.push( createBlock('ctlh/section-prototype', {}, current) );
		}

		data.dispatch('core/block-editor').resetBlocks( introBlocks.concat(sections) );
	}

	// ---------------------------------------------------------------------------
	// Split into New Section
	// ---------------------------------------------------------------------------

	/**
	 * Split content at the currently selected block.
	 *
	 * Top-level context: all blocks from the selected block onward are wrapped
	 * in a new Section block. Blocks before the split are untouched.
	 *
	 * Inside a Section: the selected block and all following blocks inside that
	 * Section are moved into a new Section inserted immediately after the parent.
	 */
	function splitIntoNewSection() {
		const store      = data.select('core/block-editor');
		const selectedId = store.getSelectedBlockClientId();

		if ( ! selectedId ) {
			window.alert('Click inside a block first to mark the split point.');
			return;
		}

		// parentId is '' for top-level blocks, or a clientId for nested blocks.
		const parentId  = store.getBlockRootClientId(selectedId);
		const siblings  = parentId ? store.getBlocks(parentId) : store.getBlocks();
		const splitIdx  = siblings.findIndex(function (b) { return b.clientId === selectedId; });

		if ( splitIdx < 0 ) {
			return; // block not found in its reported parent — shouldn't happen
		}

		// The blocks that will move into the new Section (cloned for fresh clientIds).
		const toMove    = siblings.slice(splitIdx).map(function (b) { return cloneBlock(b); });
		// The blocks that stay in the current context.
		const toKeep    = siblings.slice(0, splitIdx);
		const newSection = createBlock('ctlh/section-prototype', {}, toMove);

		if ( ! parentId ) {
			// ── Top-level ────────────────────────────────────────────────────────
			// Replace the full block list: keep everything before the split,
			// append the new Section. Undo-able via Ctrl+Z.
			const before = store.getBlocks().slice(0, splitIdx);
			data.dispatch('core/block-editor').resetBlocks( before.concat([newSection]) );
		} else {
			// ── Inside a Section (or other parent block) ─────────────────────────
			// 1. Trim the parent's innerBlocks to only what's before the split.
			// 2. Insert the new Section immediately after the parent.
			const grandparentId   = store.getBlockRootClientId(parentId);
			const parentSiblings  = grandparentId ? store.getBlocks(grandparentId) : store.getBlocks();
			const parentIdx       = parentSiblings.findIndex(function (b) { return b.clientId === parentId; });

			data.dispatch('core/block-editor').replaceInnerBlocks(parentId, toKeep, false);
			data.dispatch('core/block-editor').insertBlock(
				newSection,
				parentIdx + 1,
				grandparentId || undefined,
				false  // do not update selection
			);
		}
	}

	// ---------------------------------------------------------------------------
	// Sidebar panels
	// ---------------------------------------------------------------------------

	registerPlugin('ctlh-split-into-section', {
		render: function () {
			// Reactively read the selected block so the hint label updates live.
			const selectedBlock = useSelect(function (select) {
				const id = select('core/block-editor').getSelectedBlockClientId();
				if ( ! id ) return null;
				return select('core/block-editor').getBlock(id);
			}, []);

			const hint = selectedBlock
				? 'Selected: ' + selectedBlock.name.replace('core/', '')
				: 'No block selected — click a block first.';

			return el(
				PluginDocumentSettingPanel,
				{
					name:  'ctlh-split-into-section',
					title: 'Split into Section',
					icon:  'editor-break',
				},
				el(PanelRow, null,
					el('p', {
						style: { fontSize: '12px', color: '#666', margin: '0 0 6px' },
					}, 'Creates a new Section block starting at the selected block. All following content moves into that Section.')
				),
				el(PanelRow, null,
					el('p', {
						style: { fontSize: '11px', color: '#888', margin: '0 0 10px', fontStyle: 'italic' },
					}, hint)
				),
				el(PanelRow, null,
					el(Button, {
						variant:  'secondary',
						onClick:   splitIntoNewSection,
						disabled:  ! selectedBlock,
						style:    { width: '100%', justifyContent: 'center' },
					}, 'Split into New Section')
				)
			);
		},
	});

	registerPlugin('ctlh-convert-to-sections', {
		render: function () {
			return el(
				PluginDocumentSettingPanel,
				{
					name:  'ctlh-convert-to-sections',
					title: 'Convert to Sections',
					icon:  'excerpt-view',
				},
				el(PanelRow, null,
					el('p', {
						style: { fontSize: '12px', color: '#666', margin: '0 0 10px' },
					}, 'Splits the post at each H2 heading, wrapping each heading and its following content into a CTLawHelp Section block.')
				),
				el(PanelRow, null,
					el(Button, {
						variant: 'secondary',
						onClick:  convertToSections,
						style:   { width: '100%', justifyContent: 'center' },
					}, 'Convert to Sections')
				)
			);
		},
	});

})(window.wp.plugins, window.wp.editPost, window.wp.element, window.wp.components, window.wp.data, window.wp.blocks);
