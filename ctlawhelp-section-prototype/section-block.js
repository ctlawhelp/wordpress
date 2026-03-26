(function (blocks, element, blockEditor, data, components) {
	const el = element.createElement;
	const { Fragment } = element;
	const { useSelect, useDispatch } = data;
	const { InnerBlocks, BlockControls, useBlockProps } = blockEditor;
	const { ToolbarGroup, ToolbarButton } = components;

	blocks.registerBlockType('ctlh/section-prototype', {
		title: 'CTLawHelp Section',
		icon: 'excerpt-view',
		category: 'layout',
		description: 'Prototype section block for CTLawHelp articles.',
		supports: {
			html: false,
		},

		edit: function (props) {
			const { clientId } = props;

			const blockProps = useBlockProps({
				style: {
					border: '2px solid #c8d6e5',
					borderRadius: '6px',
					marginBottom: '16px',
					background: '#fafafa',
					overflow: 'hidden',
				},
			});

			const { duplicateBlocks, removeBlock } = useDispatch('core/block-editor');

			// Read the first heading block nested inside this section so the
			// editor label stays in sync as the author types.
			const sectionLabel = useSelect(function (select) {
				const innerBlocks = select('core/block-editor').getBlocks(clientId);
				for (let i = 0; i < innerBlocks.length; i++) {
					if (innerBlocks[i].name === 'core/heading') {
						const raw = innerBlocks[i].attributes.content || '';
						// Strip any inline HTML tags (e.g. <strong>) for the label.
						const plain = raw.replace(/<[^>]*>/g, '').trim();
						return plain || null;
					}
				}
				return null;
			}, [clientId]);

			return el(
				Fragment,
				null,
				// Toolbar controls — rendered into the block toolbar via BlockControls.
				el(
					BlockControls,
					null,
					el(
						ToolbarGroup,
						null,
						el(ToolbarButton, {
							icon: 'admin-page',
							label: 'Duplicate Section',
							onClick: function () {
								duplicateBlocks([clientId]);
							},
						}),
						el(ToolbarButton, {
							icon: 'trash',
							label: 'Remove Section',
							onClick: function () {
								if ( window.confirm('Delete this entire section and all its contents?') ) {
									removeBlock(clientId);
								}
							},
						})
					)
				),
				// Block content
				el(
					'div',
					blockProps,
					// Section header bar with inline Duplicate / Remove controls.
					el(
						'div',
						{
							style: {
								background: '#c8d6e5',
								padding: '6px 12px',
								display: 'flex',
								alignItems: 'center',
								gap: '8px',
							},
							// Prevent clicks on the decorative header area from bubbling to
							// Gutenberg's block wrapper, which would trigger scrollIntoView.
							onClick: function (e) { e.stopPropagation(); },
						},
						el('span', {
							style: {
								fontSize: '11px',
								fontWeight: '700',
								textTransform: 'uppercase',
								letterSpacing: '0.05em',
								color: '#2c3e50',
							},
						}, 'Section'),
						el('span', {
							style: {
								fontSize: '13px',
								color: sectionLabel ? '#2c3e50' : '#7f8c8d',
								fontStyle: sectionLabel ? 'normal' : 'italic',
							},
						}, sectionLabel || '\u2014 first heading becomes the section label'),
						// Controls pushed to the far right of the header bar.
						el(
							'div',
							{ style: { marginLeft: 'auto', display: 'flex', gap: '4px' } },
							el('button', {
								type: 'button',
								title: 'Duplicate this section',
								style: {
									background: 'none',
									border: '1px solid rgba(44,62,80,0.3)',
									borderRadius: '3px',
									padding: '2px 7px',
									fontSize: '11px',
									color: '#2c3e50',
									cursor: 'pointer',
									lineHeight: '1.6',
								},
								// stopPropagation prevents Gutenberg re-focusing the block
								// insertion point and triggering an unwanted scroll.
								onClick: function (e) {
									e.stopPropagation();
									duplicateBlocks([clientId]);
								},
							}, 'Duplicate'),
							el('button', {
								type: 'button',
								title: 'Remove this section',
								style: {
									background: 'none',
									border: '1px solid rgba(44,62,80,0.3)',
									borderRadius: '3px',
									padding: '2px 7px',
									fontSize: '11px',
									color: '#c0392b',
									cursor: 'pointer',
									lineHeight: '1.6',
								},
								onClick: function (e) {
									e.stopPropagation();
									if ( window.confirm('Delete this entire section and all its contents?') ) {
										removeBlock(clientId);
									}
								},
							}, 'Remove'),
						),
					),
					// Inner content
					el(
						'div',
						{ style: { padding: '12px 16px' } },
						el(InnerBlocks, {
							template: [
								['core/heading', { placeholder: 'Section heading', level: 2 }],
								['core/paragraph', { placeholder: 'Start writing...' }],
							],
							templateLock: false,
							allowedBlocks: [
								'core/heading',
								'core/paragraph',
								'core/list',
								'core/image',
								'core/quote',
								'core/separator',
								'core/table',
								'core/buttons',
								'core/button',
								'core/file',
								'core/html',
							],
							// renderAppender removed: ButtonBlockAppender registers itself as
							// the focused insertion point, causing Gutenberg to scroll to the
							// bottom of every InnerBlocks area when the block is selected.
							// The default Gutenberg appender (slash command / floating +) works
							// without triggering the scroll behaviour.
						})
					)
				)
			);
		},

		save: function () {
			return el(InnerBlocks.Content);
		},
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.data, window.wp.components);
