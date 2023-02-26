<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log import/export related actions
 */
class NashaatGravityImportExport extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'check_admin_referer',
			'callback' => 'log_exported_forms'
		),
		array(
			'name' => 'gform_forms_post_import',
			'callback' => 'log_imported_forms'
		),
		array(
			'name' => 'gform_post_export_entries',
			'callback' => 'log_exported_entries'
		),
	);

	protected $filters = array(
		array(
			'name' => 'gform_export_lines',
			'callback' => 'count_exported_data'
		),
	);

	/**
	 * Track the number of exported entries
	 *
	 * @var integer
	 */
	private $records_count = 0;

	protected $context = 'gravity_data';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Log exported forms data
	 *
	 * @param string $action_type Action type
	 * @return void
	 */
	protected function log_exported_forms( $action_type ) {

		if ( $action_type !== 'gf_export_forms' ) {
			return;
		}

		if ( empty( $_POST['export_forms'] ) ) {
			return;
		}

		$selected_forms = rgpost( 'gf_form_id' );
		$exported_forms = array();

		foreach ( $selected_forms as $selected_form ) {
			$form = GFAPI::get_form( $selected_form );

			$exported_forms[] = array(
				'title' => $form['title'],
				'id' => $form['id']
			);
		}

		$this->event = 'exported';
		$this->log_info['type'] = 'forms';
		$this->log_info['data'] = $exported_forms;
	}

	/**
	 * Log imported forms data
	 *
	 * @param array $forms An array of form objects.
	 * @return void
	 */
	protected function log_imported_forms( $forms ) {

		$imported_forms = array();

		foreach ( $forms as $form ) {
			$imported_forms[] = array(
				'title' => $form['title'],
				'id' => $form['id']
			);
		}

		$this->event = 'imported';
		$this->log_info['type'] = 'forms';
		$this->log_info['data'] = $imported_forms;
	}

	/**
	 * Log exported entries data
	 *
	 * @param array  $form The form object to get the entries from.
	 * @param string $start_date The start date from where the entries exported will begin.
	 * @param string $end_date The end date on which the entry export will stop.
	 * @param array  $fields he field IDs from which entries are being exported.
	 * @param string $export_id The unique ID for the export.
	 * @return void
	 */
	protected function log_exported_entries( $form, $start_date, $end_date, $fields, $export_id ) {

		$fields_labels = array();
		foreach ( $fields as $field_id ) {
			$field = RGFormsModel::get_field( $form, $field_id );
			$fields_labels[] = $field->label;
		}

		$exported_entries = array(
			'title' => $form['title'],
			'id' => $form['id'],
			'start_date' => $start_date,
			'end_date' => $end_date,
			'fields' => array_unique( $fields_labels ),
			// -1 is for header row
			'count' => $this->records_count - 1,
			'export_id' => $export_id
		);

		$this->event = 'exported';
		$this->log_info['type'] = 'entries';
		$this->log_info['data'] = $exported_entries;
	}

	/**
	 * Use this filter to count the number of exported entries
	 *
	 * @param string $lines The lines to be included in the .csv export.
	 * @return string $lines variables
	 */
	protected function count_exported_data( $lines ) {
		$trim_lines = trim( $lines );
		$split_lines = preg_split( '/\r\n|\r|\n/', trim( $trim_lines ) );
		$this->records_count += count( $split_lines );
		return $lines;
	}


	/**
	 * Add translations for WP Crontrol
	 *
	 * @param array $translations Current translations
	 * @return array List of translations to be added
	 */
	public function set_translations( array $translations ) {
		return array(
			'list' => __( 'List', 'nashaat' ),
			'entries' => __( 'Entries', 'nashaat' ),
			'start_date' => __( 'Start Date', 'nashaat' ),
			'end_date' => __( 'End Date', 'nashaat' ),
			'fields' => __( 'Fields', 'nashaat' ),
			'export_id' => __( 'Export ID', 'nashaat' ),
			'count' => __( 'Count', 'nashaat' ),
			'all' => __( 'All', 'nashaat' ),
		);
	}

	/**
	 * Render html output
	 *
	 * @param array                $log_info Log info array
	 * @param string               $event Event name
	 * @param array                $item Row details
	 * @param NashaatRenderLogInfo $render_class Render class instance
	 * @return string Html string
	 */
	public function render_log_info_output( $log_info, string $event, array $item, $render_class ) : string {

		$type = $log_info['type'];
		$output = '';

		switch ( $type ) :
			case 'forms':
				$record_count = count( $log_info['data'] );
				$output = '<strong>';
				$output .= sprintf( __( '%1$s %2$s were %3$s', 'nashaat' ), $record_count, $type, $event );
				$output .= '</strong>';

				$output .= '<h5>' . get_nashaat_lang( 'list' ) . '</h5>';

				// Display forms list
				$output .= '<ul class="list-wrapper">';
				foreach ( $log_info['data'] as $key => $value ) {
					$output .= sprintf( '<li>%s (#%s)</li>', $value['title'], $value['id'] );
				}
				$output .= '</ul>';
				break;
				$output = $this->render_imported_log_info_output( $log_info, $item, $render_class );
				break;

			case 'entries':
				list(
					'title' => $title,
					'id' => $id,
					'start_data' => $start_data,
					'end_data' => $end_data,
					'fields' => $fields,
					'count' => $count,
					'export_id' => $export_id
				) = $log_info['data'];

				$form_data = array(
					'title' => $title,
					'id' => $id,
				);
				$output .= $render_class::array_to_html( $form_data );

				$entries_data = array(
					'start_date' => $start_data == '' ? get_nashaat_lang( 'all' ) : $start_data,
					'end_date' => $end_data == '' ? get_nashaat_lang( 'all' ) : $end_data,
					'fields' => implode( ', ', $fields ),
					'count' => $count,
					'export_id' => $export_id
				);
				$output .= $render_class::array_to_html( $entries_data, get_nashaat_lang( 'entries' ) );

			endswitch;
		return $output;
	}
}