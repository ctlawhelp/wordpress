<?php
/**
 * Plugin Name: Legal Aid Tax Bread – Drupal Breadcrumb Overrides
 * Description: Temporarily rewrites NSMI breadcrumb links (Yoast) to point at ctlawhelp.org Drupal self-help section IDs.
 * Version: 0.1.0
 * Author: CTLawHelp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Configure this once. Disable by setting to false.
 */
if ( ! defined( 'LATB_DRUPAL_BREADCRUMBS_ENABLED' ) ) {
	define( 'LATB_DRUPAL_BREADCRUMBS_ENABLED', true );
}

/**
 * Base URLs for English and Spanish.
 */
if ( ! defined( 'LATB_DRUPAL_BREADCRUMBS_BASE_EN' ) ) {
	define( 'LATB_DRUPAL_BREADCRUMBS_BASE_EN', 'https://ctlawhelp.org/en/self-help' );
}
if ( ! defined( 'LATB_DRUPAL_BREADCRUMBS_BASE_ES' ) ) {
	define( 'LATB_DRUPAL_BREADCRUMBS_BASE_ES', 'https://ctlawhelp.org/es/self-help' );
}

// Optional debug logging (writes to PHP error_log).
if ( ! defined( 'LATB_DRUPAL_BREADCRUMBS_DEBUG' ) ) {
	define( 'LATB_DRUPAL_BREADCRUMBS_DEBUG', false );
}

/**
 * Drupal term tables: English and Spanish
 *
 * We match WordPress terms by NAME (and parent) instead of guessing slugs.
 * This avoids slug mismatches and handles duplicates safely (e.g. same label under different parents).
 */
function latb_dbo_drupal_terms_table_en(): array {
	return [
		[ 'tid' => 538, 'pid' => 0,   'name' => 'Benefits & Social Services' ],
		[ 'tid' => 573, 'pid' => 538, 'name' => 'Disability Rights and Assistance' ],
		[ 'tid' => 576, 'pid' => 538, 'name' => 'Food, Cash, and Housing' ],
		[ 'tid' => 574, 'pid' => 538, 'name' => 'Health Insurance, Medicare, and Medicaid' ],
		[ 'tid' => 577, 'pid' => 538, 'name' => 'Unemployment Benefits' ],
		[ 'tid' => 531, 'pid' => 0,   'name' => 'Debt & Collections' ],
		[ 'tid' => 542, 'pid' => 531, 'name' => 'Collections and Bankruptcy' ],
		[ 'tid' => 543, 'pid' => 531, 'name' => 'Credit Reports and Identity Theft' ],
		[ 'tid' => 545, 'pid' => 531, 'name' => 'Small Claims Court' ],
		[ 'tid' => 723, 'pid' => 531, 'name' => 'Utilities - Heat, Hot Water, and Electricity' ],
		[ 'tid' => 534, 'pid' => 0,   'name' => 'Family & Safety' ],
		[ 'tid' => 557, 'pid' => 534, 'name' => 'Abuse and Violence' ],
		[ 'tid' => 555, 'pid' => 534, 'name' => 'Child Custody, Visitation, and Support' ],
		[ 'tid' => 558, 'pid' => 534, 'name' => "Children's Rights" ],
		[ 'tid' => 556, 'pid' => 534, 'name' => 'Divorce and Separation' ],
		[ 'tid' => 535, 'pid' => 0,   'name' => 'Homes & Apartments' ],
		[ 'tid' => 675, 'pid' => 535, 'name' => 'Homelessness' ],
		[ 'tid' => 560, 'pid' => 535, 'name' => 'Foreclosure' ],
		[ 'tid' => 562, 'pid' => 535, 'name' => 'Landlord/Tenant' ],
		[ 'tid' => 559, 'pid' => 535, 'name' => 'Utilities - Heat, Hot Water, and Electricity' ],
		[ 'tid' => 536, 'pid' => 0,   'name' => 'Immigration & Citizenship' ],
		[ 'tid' => 541, 'pid' => 0,   'name' => 'Medical & Health Care' ],
		[ 'tid' => 674, 'pid' => 541, 'name' => 'Family and Medical Leave (FMLA)' ],
		[ 'tid' => 579, 'pid' => 541, 'name' => 'Health Insurance, Medicare, and Medicaid' ],
		[ 'tid' => 686, 'pid' => 541, 'name' => 'Health and Wellness' ],
		[ 'tid' => 580, 'pid' => 541, 'name' => 'Nursing Homes and Burial Plots' ],
		[ 'tid' => 578, 'pid' => 541, 'name' => 'Nursing Homes, Living Wills, Advance Directives' ],
		[ 'tid' => 532, 'pid' => 0,   'name' => 'School & Education' ],
		[ 'tid' => 537, 'pid' => 0,   'name' => 'Seniors' ],
		[ 'tid' => 567, 'pid' => 537, 'name' => 'Medical and Health Care' ],
		[ 'tid' => 570, 'pid' => 537, 'name' => 'Other Benefits and Social Services' ],
		[ 'tid' => 569, 'pid' => 537, 'name' => 'Financial and Debt Issues' ],
		[ 'tid' => 564, 'pid' => 537, 'name' => 'Power of Attorney and End of Life Issues' ],
		[ 'tid' => 571, 'pid' => 537, 'name' => 'Housing' ],
		[ 'tid' => 563, 'pid' => 537, 'name' => 'Nursing Homes' ],
		[ 'tid' => 565, 'pid' => 537, 'name' => 'Other Resources' ],
		[ 'tid' => 533, 'pid' => 0,   'name' => 'Work & Unemployment' ],
		[ 'tid' => 779, 'pid' => 533, 'name' => 'Farmworker Rights' ],
		[ 'tid' => 553, 'pid' => 533, 'name' => 'Problems with Hours and Pay' ],
		[ 'tid' => 551, 'pid' => 533, 'name' => 'Unemployment Compensation' ],
		[ 'tid' => 552, 'pid' => 533, 'name' => "Workers' Rights" ],
		[ 'tid' => 683, 'pid' => 0,   'name' => 'LGBTQ+' ],
		[ 'tid' => 812, 'pid' => 0,   'name' => 'Disaster Assistance' ],
		[ 'tid' => 826, 'pid' => 812, 'name' => 'General Information' ],
		[ 'tid' => 822, 'pid' => 812, 'name' => 'Before a Disaster' ],
		[ 'tid' => 824, 'pid' => 812, 'name' => 'During a Disaster' ],
		[ 'tid' => 821, 'pid' => 812, 'name' => 'After a Disaster' ],
		[ 'tid' => 539, 'pid' => 0,   'name' => 'More Legal Topics' ],
		[ 'tid' => 681, 'pid' => 539, 'name' => 'Criminal Records and Reentry' ],
		[ 'tid' => 684, 'pid' => 539, 'name' => 'Military and Veterans' ],
		[ 'tid' => 682, 'pid' => 539, 'name' => 'Representing Yourself in Court' ],
	];
}

function latb_dbo_drupal_terms_table_es(): array {
	return [
		[ 'tid' => 538, 'pid' => 0,   'name' => 'Beneficios y servicios sociales' ],
		[ 'tid' => 573, 'pid' => 538, 'name' => 'Derechos y asistencia de incapacidad' ],
		[ 'tid' => 576, 'pid' => 538, 'name' => 'Comida, efectivo y vivienda' ],
		[ 'tid' => 574, 'pid' => 538, 'name' => 'Seguro de salud, Medicare y Medicaid' ],
		[ 'tid' => 577, 'pid' => 533, 'name' => 'Compensación de desempleo' ],
		[ 'tid' => 531, 'pid' => 0,   'name' => 'Deuda y colecciones' ],
		[ 'tid' => 542, 'pid' => 531, 'name' => 'Colecciones y bancarrota' ],
		[ 'tid' => 543, 'pid' => 531, 'name' => 'Reportes de credito y robo de identidad' ],
		[ 'tid' => 545, 'pid' => 531, 'name' => 'Corte de reclamos menores' ],
		[ 'tid' => 723, 'pid' => 531, 'name' => 'Servicios públicos - Calefacción, agua caliente y electricidad' ],
		[ 'tid' => 534, 'pid' => 0,   'name' => 'Familia y seguridad' ],
		[ 'tid' => 557, 'pid' => 534, 'name' => 'Abuso y violencia' ],
		[ 'tid' => 555, 'pid' => 534, 'name' => 'Custodia, visitación y manutención de niños' ],
		[ 'tid' => 558, 'pid' => 534, 'name' => 'Derechos de niños' ],
		[ 'tid' => 556, 'pid' => 534, 'name' => 'Divorcio y separación' ],
		[ 'tid' => 535, 'pid' => 0,   'name' => 'Casas y apartamentos' ],
		[ 'tid' => 675, 'pid' => 535, 'name' => 'Sin hogar' ],
		[ 'tid' => 560, 'pid' => 535, 'name' => 'Ejecución hipotecaria' ],
		[ 'tid' => 562, 'pid' => 535, 'name' => 'Propietario/Arrendatario' ],
		[ 'tid' => 559, 'pid' => 535, 'name' => 'Servicios públicos - Calefacción, agua caliente y electricidad' ],
		[ 'tid' => 536, 'pid' => 0,   'name' => 'Inmigración y ciudadanía' ],
		[ 'tid' => 541, 'pid' => 0,   'name' => 'Médicos y cuidado de la salud' ],
		[ 'tid' => 674, 'pid' => 541, 'name' => 'El Acta de la Familia y Ausencia Médica' ],
		[ 'tid' => 579, 'pid' => 541, 'name' => 'Seguro de salud, Medicare y Medicaid' ],
		[ 'tid' => 686, 'pid' => 541, 'name' => 'Salud y bienestar' ],
		[ 'tid' => 578, 'pid' => 541, 'name' => 'Centros de convalecencia, y directivas anticipadas' ],
		[ 'tid' => 532, 'pid' => 0,   'name' => 'Escuela y educación' ],
		[ 'tid' => 537, 'pid' => 0,   'name' => 'Derechos de ancianos' ],
		[ 'tid' => 567, 'pid' => 537, 'name' => 'Asuntos Médicos y de Salud' ],
		[ 'tid' => 570, 'pid' => 537, 'name' => 'Otros beneficios y servicios sociales' ],
		[ 'tid' => 569, 'pid' => 537, 'name' => 'Problemas financieros' ],
		[ 'tid' => 564, 'pid' => 537, 'name' => 'Poderes de abogado y problemas con final de la vida' ],
		[ 'tid' => 571, 'pid' => 537, 'name' => 'Vivienda' ],
		[ 'tid' => 563, 'pid' => 537, 'name' => 'Asilos de ancianos y lotes de cementerio' ],
		[ 'tid' => 533, 'pid' => 0,   'name' => 'Desempleo y empleo' ],
		[ 'tid' => 779, 'pid' => 533, 'name' => 'Derechos de trabajadores agrícolas' ],
		[ 'tid' => 553, 'pid' => 533, 'name' => 'Problemas con las horas y el pago' ],
		[ 'tid' => 551, 'pid' => 533, 'name' => 'Compensación de desempleo' ],
		[ 'tid' => 552, 'pid' => 533, 'name' => 'Derechos de los trabajadores' ],
		[ 'tid' => 683, 'pid' => 0,   'name' => 'LGBTQ+' ],
		[ 'tid' => 539, 'pid' => 0,   'name' => 'Otros temas legales' ],
		[ 'tid' => 681, 'pid' => 539, 'name' => 'Records criminales' ],
		[ 'tid' => 684, 'pid' => 539, 'name' => 'Militares y veteranos' ],
		[ 'tid' => 682, 'pid' => 539, 'name' => 'Representándose en la corte' ],
	];
}

/**
 * Get the appropriate term table based on post language
 */
function latb_dbo_drupal_terms_table( $lang = 'en' ): array {
	if ( $lang === 'es' ) {
		return latb_dbo_drupal_terms_table_es();
	}
	return latb_dbo_drupal_terms_table_en();
}

function latb_dbo_normalize_name( string $name ): string {
	$name = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
	$name = trim( $name );
	$name = str_replace( [ "", "" ], '', $name );
	$name = mb_strtolower( $name, 'UTF-8' );
	$name = str_replace( [ '&', '+' ], ' and ', $name );
	$name = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $name );
	$name = preg_replace( '/\s+/u', ' ', $name );
	return trim( $name );
}

/**
 * Resolve a WordPress nsmi_category term to a Drupal term row (tid/pid) using name + parent.
 */
function latb_dbo_resolve_drupal_row_for_wp_term( WP_Term $wp_term, array $visited_wp_term_ids = [], $lang = 'en' ): array {
	static $cache = [];
	$cache_key = $lang;
	
	if ( ! isset( $cache[ $cache_key ] ) ) {
		$index_by_name = [];
		$row_by_tid = [];
		
		foreach ( latb_dbo_drupal_terms_table( $lang ) as $row ) {
			$row_by_tid[ (int) $row['tid'] ] = [
				'tid' => (int) $row['tid'],
				'pid' => (int) $row['pid'],
				'name' => (string) $row['name'],
				'norm' => latb_dbo_normalize_name( (string) $row['name'] ),
			];
			$index_by_name[ $row_by_tid[ (int) $row['tid'] ]['norm'] ][] = (int) $row['tid'];
		}
		
		$cache[ $cache_key ] = [
			'index_by_name' => $index_by_name,
			'row_by_tid' => $row_by_tid,
		];
	}
	
	$index_by_name = $cache[ $cache_key ]['index_by_name'];
	$row_by_tid = $cache[ $cache_key ]['row_by_tid'];

	$norm = latb_dbo_normalize_name( (string) $wp_term->name );
	$candidates = $index_by_name[ $norm ] ?? [];
	if ( empty( $candidates ) ) {
		if ( LATB_DRUPAL_BREADCRUMBS_DEBUG ) {
			error_log( 'LATB Drupal Breadcrumbs: No Drupal match for WP term name "' . $wp_term->name . '" (ID ' . $wp_term->term_id . ', slug ' . $wp_term->slug . ')' );
		}
		return [ 'tid' => 0, 'pid' => 0 ];
	}

	if ( count( $candidates ) === 1 ) {
		$tid = (int) $candidates[0];
		return [ 'tid' => $tid, 'pid' => (int) $row_by_tid[ $tid ]['pid'] ];
	}

	// Disambiguate duplicates using parent.
	$parent_tid = 0;
	if ( ! empty( $wp_term->parent ) ) {
		if ( in_array( $wp_term->parent, $visited_wp_term_ids, true ) ) {
			$parent_tid = 0;
		} else {
			$visited_wp_term_ids[] = (int) $wp_term->term_id;
			$parent_wp = get_term( (int) $wp_term->parent, 'nsmi_category' );
			if ( $parent_wp && ! is_wp_error( $parent_wp ) ) {
				$parent_row = latb_dbo_resolve_drupal_row_for_wp_term( $parent_wp, $visited_wp_term_ids, $lang );
				$parent_tid = (int) ( $parent_row['tid'] ?? 0 );
			}
		}
	}

	// Prefer matching parent.
	if ( $parent_tid > 0 ) {
		foreach ( $candidates as $tid ) {
			$tid = (int) $tid;
			if ( (int) $row_by_tid[ $tid ]['pid'] === $parent_tid ) {
				return [ 'tid' => $tid, 'pid' => (int) $row_by_tid[ $tid ]['pid'] ];
			}
		}
	}

	// Otherwise, prefer top-level if WP term is top-level.
	if ( empty( $wp_term->parent ) ) {
		foreach ( $candidates as $tid ) {
			$tid = (int) $tid;
			if ( (int) $row_by_tid[ $tid ]['pid'] === 0 ) {
				return [ 'tid' => $tid, 'pid' => 0 ];
			}
		}
	}

	// Fallback: first candidate.
	$tid = (int) $candidates[0];
	return [ 'tid' => $tid, 'pid' => (int) $row_by_tid[ $tid ]['pid'] ];
}

function latb_dbo_build_drupal_url_for_term( WP_Term $term, $lang = 'en' ): string {
	$row = latb_dbo_resolve_drupal_row_for_wp_term( $term, [], $lang );
	$tid = (int) ( $row['tid'] ?? 0 );
	$pid = (int) ( $row['pid'] ?? 0 );
	if ( $tid <= 0 ) {
		return '';
	}

	$base = ( $lang === 'es' ) ? untrailingslashit( LATB_DRUPAL_BREADCRUMBS_BASE_ES ) : untrailingslashit( LATB_DRUPAL_BREADCRUMBS_BASE_EN );
	if ( $pid > 0 ) {
		return $base . '/' . $pid . '/' . $tid;
	}
	return $base . '/' . $tid;
}

/**
 * Rewrite Yoast breadcrumb links for nsmi_category.
 *
 * We run AFTER legal-aid-tax-bread (priority > 15) and only adjust the url.
 */
add_filter('wpseo_breadcrumb_links', function( $links ) {
	if ( ! LATB_DRUPAL_BREADCRUMBS_ENABLED ) {
		return $links;
	}
	if ( ! taxonomy_exists( 'nsmi_category' ) ) {
		return $links;
	}
	if ( ! is_singular( [ 'post', 'legal_article', 'interactive_guide' ] ) ) {
		return $links;
	}

	$post_id = get_the_ID();
	$primary_id = (int) get_post_meta( $post_id, '_primary_nsmi_category', true );
	if ( ! $primary_id ) {
		return $links;
	}
	
	// Detect post language for language-specific Drupal URLs
	$lang = 'en';
	if ( function_exists( 'pll_get_post_language' ) ) {
		$post_lang = pll_get_post_language( $post_id );
		if ( $post_lang ) {
			$lang = $post_lang;
		}
	}

	foreach ( $links as &$link ) {
		if ( empty( $link['taxonomy'] ) || $link['taxonomy'] !== 'nsmi_category' ) {
			continue;
		}

		$term_id = isset( $link['term_id'] ) ? (int) $link['term_id'] : 0;
		if ( ! $term_id ) {
			continue;
		}

		$term = get_term( $term_id, 'nsmi_category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$drupal_url = latb_dbo_build_drupal_url_for_term( $term, $lang );
		if ( $drupal_url ) {
			$link['url'] = $drupal_url;
		}
	}
	unset($link);

	return $links;
}, 50);
