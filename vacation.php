<?php

/*
 +-----------------------------------------------------------------------+
 | Vacation Module for RoundCube                                         |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
 */

define ('PLUGIN_SUCCESS', 0);
define ('PLUGIN_ERROR_DEFAULT', 1);
define ('PLUGIN_ERROR_CONNECT', 2);
define ('PLUGIN_ERROR_PROCESS', 3);
define ('PLUGIN_ERROR_DATE', 4);

class vacation extends rcube_plugin
{
	public $task = 'settings';
	private $rc;
	private $obj;

	/*
	 * Initializes the plugin.
	 */
	public function init()
	{
		$rcmail = rcmail::get_instance();
		$this->rc = &$rcmail;
		$this->add_texts('localization/', true);

		$this->rc->output->add_label('vacation');
		$this->register_action('plugin.vacation', array($this, 'vacation_init'));
		$this->register_action('plugin.vacation-save', array($this, 'vacation_save'));
		$this->include_script('vacation.js');

		$this->load_config();

		if ($this->rc->config->get('vacation_gui_vacationdate', false) && $this->rc->config->get('vacation_jquery_calendar', false))
		{
			$format = $this->rc->config->get('vacation_jquery_dateformat', 'mm/dd/yy');
			if($rcmail->output->type === "html")
			    $this->rc->output->add_script("calendar_format='" . $format . "';");
			$this->include_script('vacation_calendar.js');
		}

		require_once ($this->home . '/lib/rcube_vacation.php');
		$this->obj = new rcube_vacation();
	}

	/*
	 * Plugin initialization function.
	 */
	public function vacation_init()
	{
		$this->read_data();

		$this->register_handler('plugin.body', array($this, 'vacation_form'));
		$this->rc->output->set_pagetitle($this->gettext('vacation'));
		$this->rc->output->send('plugin');
	}

	/*
	 * Plugin save function.
	 */
	public function vacation_save()
	{
		$this->write_data();

		$this->register_handler('plugin.body', array($this, 'vacation_form'));
		$this->rc->output->set_pagetitle($this->gettext('vacation'));
		rcmail::get_instance()->overwrite_action('plugin.vacation');
		$this->rc->output->send('plugin');
	}

	protected function get_timestamp($date)
	{
		if ($date === '') {
			return null;
		} else {
			$d = $this->my_date_parse_from_format($this->rc->config->get('vacation_dateformat', 'm/d/Y'), $date);
			if (!is_array($d) || $d['month'] === false || $d['day'] === false || $d['year'] === false)
			{
				return false;
			}
			return gmmktime(0,0,0, $d['month'], $d['day'], $d['year']);
		}
	}

	protected function add_date_field($table, $field_id, $val, $format)
	{
		$input = new html_inputfield(array('name' => '_' . $field_id, 'id' => $field_id, 'size' => 10));
		$table->add('title', html::label($field_id, rcmail::Q($this->gettext($field_id))));
		if ($val !== null) {
			if ($val == 0)
			{
				$val = time();
			}
			$date_val = substr($this->rc->config->get('vacation_sql_dsn'), 0, 5) == "mysql" ? date($format, $val) : $val;
		} else {
			$date_val = '';
		}
		$table->add(null, $input->show($date_val));
	}

	/*
	 * Plugin UI form function.
	 */
	public function vacation_form()
	{
		$table = new html_table(array('cols' => 2));

		$field_id = 'vacationenable';
		$input_vacationenable = new html_checkbox(array('name' => '_vacationenable', 'id' => $field_id, 'value' => 1));
		$table->add('title', html::label($field_id, rcmail::Q($this->gettext('vacationenable'))));
		$table->add(null, $input_vacationenable->show($this->obj->is_vacation_enable() === true || $this->obj->is_vacation_enable() == "1" || $this->obj->is_vacation_enable() == "t" || $this->obj->is_vacation_enable() == "y" || $this->obj->is_vacation_enable() == "yes" ? 1 : 0));

		if ($this->rc->config->get('vacation_gui_vacationdate', false))
		{
			$format = $this->rc->config->get('vacation_dateformat', 'm/d/Y');
			$this->add_date_field($table, 'vacationstart', $this->obj->get_vacation_start(), $format);
			$this->add_date_field($table, 'vacationend', $this->obj->get_vacation_end(), $format);
		}

		if ($this->rc->config->get('vacation_gui_vacationsubject', false))
		{
			$field_id = 'vacationsubject';
			$input_vacationsubject = new html_inputfield(array('name' => '_vacationsubject', 'id' => $field_id, 'size' => 95));
			$table->add('title', html::label($field_id, rcmail::Q($this->gettext('vacationsubject'))));
			$table->add(null, $input_vacationsubject->show($this->obj->get_vacation_subject()));
		}

		$field_id = 'vacationmessage';
		if ($this->rc->config->get('vacation_gui_vacationmessage_html', false))
		{
			$this->rc->output->add_label('converting', 'editorwarning');
			// FIX: use identity mode for minimal functions
			rcube_html_editor('identity');

			$text_vacationmessage = new html_textarea(array('name' => '_vacationmessage', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 70, 'class' => 'mce_editor'));
		}
		else
		{
			$text_vacationmessage = new html_textarea(array('name' => '_vacationmessage', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 70));
		}

		$table->add('title', html::label($field_id, rcmail::Q($this->gettext('vacationmessage'))));
		$table->add(null, $text_vacationmessage->show($this->obj->get_vacation_message()));

		if ($this->rc->config->get('vacation_gui_vacationkeepcopyininbox', false))
		{
			$field_id = 'keepcopyininbox';
			$input_vacationkeepcopyininbox = new html_checkbox(array('name' => '_vacationkeepcopyininbox', 'id' => $field_id, 'value' => 1));
			$table->add('title', html::label($field_id, rcmail::Q($this->gettext('vacationkeepcopyininbox'))));
			$table->add(null, $input_vacationkeepcopyininbox->show($this->obj->is_vacation_keep_copy_in_inbox() ? 1 : 0));
		}

		if ($this->rc->config->get('vacation_gui_vacationforwarder', false))
		{
			$field_id = 'vacationforwarder';
			$input_vacationforwarder = new html_inputfield(array('name' => '_vacationforwarder', 'id' => $field_id, 'size' => 95));
			$table->add('title', html::label($field_id, rcmail::Q($this->gettext('vacationforwarder'))));
			$table->add(null, $input_vacationforwarder->show($this->obj->get_vacation_forwarder()));
		}

		$out = html::div(array('class' => "box"), html::div(array('id' => "prefs-title", 'class' => 'boxtitle'), $this->gettext('vacation')) . html::div(array('class' => "boxcontent scroller"), $table->show() . html::p(null, $this->rc->output->button(array('command' => 'plugin.vacation-save', 'type' => 'input', 'class' => 'button mainaction', 'label' => 'save')))));

		$this->rc->output->add_gui_object('vacationform', 'vacation-form');

		return $this->rc->output->form_tag(array('id' => 'vacation-form', 'name' => 'vacation-form', 'method' => 'post', 'action' => './?_task=settings&_action=plugin.vacation-save'), $out);
	}

	/*
	 * Reads plugin data.
	 */
	public function read_data()
	{
		$driver = $this->home . '/lib/drivers/' . $this->rc->config->get('vacation_driver', 'sql').'.php';

		if (!is_readable($driver))
		{
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "Vacation plugin: Unable to open driver file $driver"), true, false);

			return $this->gettext('internalerror');
		}

		require_once($driver);

		if (!function_exists('vacation_read'))
		{
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "Vacation plugin: Broken driver: $driver"), true, false);

			return $this->gettext('internalerror');
		}

		$data['username'] = $this->obj->get_username();
		$data['email'] = $this->obj->get_email();
		$data['email_local'] = $this->obj->get_email_local();
		$data['email_domain'] = $this->obj->get_email_domain();
		$data['vacation_enable'] = $this->obj->is_vacation_enable();
		$data['vacation_start'] = $this->obj->get_vacation_start();
		$data['vacation_end'] = $this->obj->get_vacation_end();
		$data['vacation_subject'] = ($this->obj->get_vacation_subject() ? $this->obj->get_vacation_subject() : $this->rc->config->get('vacation_subject_default', ''));
		$data['vacation_message'] = $this->rc->config->get('vacation_message_mime', '') . ($this->obj->get_vacation_message() ? $this->obj->get_vacation_message() : $this->rc->config->get('vacation_message_default', ''));
		$date['vacation_keepcopyininbox'] = $this->obj->is_vacation_keep_copy_in_inbox();
		$data['vacation_forwarder'] = $this->obj->get_vacation_forwarder();

		$ret = vacation_read ($data);
		switch ($ret)
		{
			case PLUGIN_ERROR_DEFAULT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverdefaulterror'), 'error');

					return false;
				}

			case PLUGIN_ERROR_CONNECT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverconnecterror'), 'error');

					return false;
				}

			case PLUGIN_ERROR_PROCESS:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverprocesserror'), 'error');

					return false;
				}
				case PLUGIN_ERROR_DATE:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverdateerror'), 'warning');
					return false;
				}

			case PLUGIN_SUCCESS:
			default:
				{
					break;
				}
		}

		if (isset($data['email']))
		{
			$this->obj->set_email($data['email']);
		}

		if (isset($data['email_local']))
		{
			$this->obj->set_email_local($data['email_local']);
		}

		if (isset($data['email_domain']))
		{
			$this->obj->set_email_domain($data['email_domain']);
		}

		if (isset($data['vacation_enable']))
		{
			$this->obj->set_vacation_enable($data['vacation_enable']);
		}

		if (isset($data['vacation_start']))
		{
			$this->obj->set_vacation_start($data['vacation_start']);
		}

		if (isset($data['vacation_end']))
		{
			$this->obj->set_vacation_end($data['vacation_end']);
		}

		if (isset($data['vacation_subject']) && (strlen ($data['vacation_subject']) > 0))
		{
			$this->obj->set_vacation_subject($data['vacation_subject']);
		}
		else
		{
			$this->obj->set_vacation_subject($this->rc->config->get('vacation_subject_default', ''));
		}

		if (isset($data['vacation_message']) && (strlen ($data['vacation_message']) > 0))
		{
			$data['vacation_message'] = str_replace($this->rc->config->get('vacation_message_mime', ''), "", $data['vacation_message']);
			$this->obj->set_vacation_message($data['vacation_message']);
		}
		else
		{
			$this->obj->set_vacation_message($this->rc->config->get('vacation_message_default', ''));
		}

		if (isset($data['vacation_keepcopyininbox']))
		{
			$this->obj->set_vacation_keep_copy_in_inbox($data['vacation_keepcopyininbox']);
		}

		if (isset($data['vacation_forwarder']))
		{
			$this->obj->set_vacation_forwarder($data['vacation_forwarder']);
		}

		return true;
	}

	/*
	 * Writes plugin data.
	 */
	public function write_data()
	{
		if (rcube_utils::get_input_value('_vacationenable', rcube_utils::INPUT_POST))
		{
			$this->obj->set_vacation_enable(true);
		}
		else
		{
			$this->obj->set_vacation_enable(false);
		}

		if ($this->rc->config->get('vacation_gui_vacationdate', false))
		{
			$d_start_time = $this->get_timestamp(rcube_utils::get_input_value('_vacationstart', rcube_utils::INPUT_POST));
			if ($d_start_time === false)
			{
				$this->rc->output->command('display_message', $this->gettext('vacationinvalidstartdate'), 'error');
				return false;
			}

			$d_end_time = $this->get_timestamp(rcube_utils::get_input_value('_vacationend', rcube_utils::INPUT_POST));
			if ($d_end_time === false)
			{
				$this->rc->output->command('display_message', $this->gettext('vacationinvalidenddate'), 'error');
				return false;
			}

			if (isset($d_start_time) && isset($d_end_time) && $d_start_time > $d_end_time)
			{
				$this->rc->output->command('display_message', $this->gettext('vacationinvaliddateinterval'), 'error');
				return false;
			}

			$this->obj->set_vacation_start($d_start_time);
			$this->obj->set_vacation_end($d_end_time);
		}

		if ($this->rc->config->get('vacation_gui_vacationsubject', false))
		{
			$subject = rcube_utils::get_input_value('_vacationsubject', rcube_utils::INPUT_POST);
			if (!is_string($subject) || (strlen($subject) == 0))
			{
				$this->rc->output->command('display_message', $this->gettext('vacationnosubject'), 'error');

				return false;
			}

			$this->obj->set_vacation_subject($subject);
		}

		$message = rcube_utils::get_input_value('_vacationmessage', rcube_utils::INPUT_POST, $this->rc->config->get('vacation_gui_vacationmessage_html', false));
		if (!is_string($message) || (strlen($message) == 0))
		{
			$this->rc->output->command('display_message', $this->gettext('vacationnomessage'), 'error');

			return false;
		}
		$this->obj->set_vacation_message($message);

		if ($this->rc->config->get('vacation_gui_keepcopyininbox', false))
		{
			if (rcube_utils::get_input_value('_vacationkeepcopyininbox', rcube_utils::INPUT_POST))
			{
				$this->obj->set_vacation_keep_copy_in_inbox(true);
			}
			else
			{
				$this->obj->set_vacation_keep_copy_in_inbox(false);
			}
		}

		if ($this->rc->config->get('vacation_gui_vacationforwarder', false))
		{
			$forwarder = rcube_utils::get_input_value('_vacationforwarder', rcube_utils::INPUT_POST);
			if (is_string($forwarder) && (strlen($forwarder) > 0))
			{
				if ($this->rc->config->get('vacation_forwarder_multiple', false))
				{
					$emails = preg_split('/' . $this->rc->config->get('vacation_forwarder_separator', ',') .'/', $forwarder);
				}
				else
				{
					$emails[] = $forwarder;
				}

				foreach ($emails as $email)
				{
					if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#', $email))
					{
						if ($this->rc->config->get('vacation_forwarder_multiple', false))
							$this->rc->output->command('display_message', $this->gettext('vacationinvalidforwarders'), 'error');
						else
							$this->rc->output->command('display_message', $this->gettext('vacationinvalidforwarder'), 'error');

						return false;
					}
				}
			}

			$this->obj->set_vacation_forwarder($forwarder);
		}

		$driver = $this->home . '/lib/drivers/' . $this->rc->config->get('vacation_driver', 'sql').'.php';

		if (!is_readable($driver))
		{
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "Vacation plugin: Unable to open driver file $driver"), true, false);

			return $this->gettext('internalerror');
		}

		require_once($driver);

		if (!function_exists('vacation_write'))
		{
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "Vacation plugin: Broken driver: $driver"), true, false);

			return $this->gettext('internalerror');
		}

		$data['username'] = $this->obj->get_username();
		$data['email'] = $this->obj->get_email();
		$data['email_local'] = $this->obj->get_email_local();
		$data['email_domain'] = $this->obj->get_email_domain();
		$data['vacation_enable'] = $this->obj->is_vacation_enable();
		$data['vacation_start'] = $this->obj->get_vacation_start();
		$data['vacation_end'] = $this->obj->get_vacation_end();
		$data['vacation_subject'] = $this->obj->get_vacation_subject();
		$data['vacation_message'] = $this->rc->config->get('vacation_message_mime', '') . ($this->obj->get_vacation_message() ? $this->obj->get_vacation_message() : $this->rc->config->get('vacation_message_default', ''));
		$data['vacation_keepcopyininbox'] = $this->obj->is_vacation_keep_copy_in_inbox();
		$data['vacation_forwarder'] = $this->obj->get_vacation_forwarder();

		if ( (substr($this->rc->config->get('vacation_sql_dsn'), 0, 5) == "pgsql") && ($data['vacation_enable'] == "" || $data['vacation_enable'] == "0") )
		{
		$data['vacation_enable'] = "false";
		}

		$ret = vacation_write ($data);
		switch ($ret)
		{
			case PLUGIN_ERROR_DEFAULT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverdefaulterror'), 'error');

					return false;
				}

			case PLUGIN_ERROR_CONNECT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverconnecterror'), 'error');

					return false;
				}

			case PLUGIN_ERROR_PROCESS:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverprocesserror'), 'error');

					return false;
				}

				case PLUGIN_ERROR_DATE:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverdateerror'), 'warning');
					return false;
				}

			case PLUGIN_SUCCESS:
			default:
				{
					$this->rc->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');

					break;
				}
		}

		if (isset($data['email']))
		{
			$this->obj->set_email($data['email']);
		}

		if (isset($data['email_local']))
		{
			$this->obj->set_email_local($data['email_local']);
		}

		if (isset($data['email_domain']))
		{
			$this->obj->set_email_domain($data['email_domain']);
		}

		if (isset($data['vacation_enable']))
		{
			$this->obj->set_vacation_enable($data['vacation_enable']);
		}

		if (isset($data['vacation_start']))
		{
			if (substr($this->rc->config->get('vacation_sql_dsn'), 0, 5) == "pgsql")
			{
				$this->obj->set_vacation_start(date($this->rc->config->get('vacation_dateformat', 'm/d/Y'), $data['vacation_start']));
			}
			else
			{
				$this->obj->set_vacation_end($data['vacation_start']);
			}
		}

		if (isset($data['vacation_end']))
		{
			if (substr($this->rc->config->get('vacation_sql_dsn'), 0, 5) == "pgsql")
			{
				$this->obj->set_vacation_end(date($this->rc->config->get('vacation_dateformat', 'm/d/Y'), $data['vacation_end']));
			}
			else
			{
				$this->obj->set_vacation_end($data['vacation_end']);
			}
		}

		if (isset($data['vacation_subject']))
		{
			$this->obj->set_vacation_subject($data['vacation_subject']);
		}
		else
		{
			$this->obj->set_vacation_subject($this->rc->config->get('vacation_subject_default', ''));
		}

		if (isset($data['vacation_message']))
		{
			$data['vacation_message'] = str_replace($this->rc->config->get('vacation_message_mime', ''), "", $data['vacation_message']);
			$this->obj->set_vacation_message($data['vacation_message']);
		}
		else
		{
			$this->obj->set_vacation_message($this->rc->config->get('vacation_message_default', ''));
		}

		if (isset($data['vacation_keepcopyininbox']))
		{
			$this->obj->set_vacation_keep_copy_in_inbox($data['vacation_keepcopyininbox']);
		}

		if (isset($data['vacation_forwarder']))
		{
			$this->obj->set_vacation_forwarder($data['vacation_forwarder']);
		}

		return true;
	}

	/*
	 * Returns the informations of a given date.
	 *
	 * @param string the date format.
	 * @param string the date.
	 * @return array the array of asscociated formats.
	 */
	private function my_date_parse_from_format($format, $date)
	{
		if (function_exists("date_parse_from_format"))
			return date_parse_from_format($format, $date);

		$ret = array();

		$formats = preg_split('/[:\/.\ \-]/', $format);
		$dates = preg_split('/[:\/.\ \-]/', $date);

		foreach($formats as $key=>$value)
		{
			switch ($value)
			{
				case 'd':
				case 'j':
					$ret['day'] = $dates[$key];
					break;

				case 'F':
				case 'M':
				case 'm':
				case 'n':
					$ret['month'] = $dates[$key];
					break;

				case 'o':
				case 'Y':
				case 'y':
					$ret['year'] = $dates[$key];
					break;

				case 'g':
				case 'G':
				case 'h':
				case 'H':
					$ret['hour'] = $dates[$key];
					break;

				case 'i':
					$ret['minute'] = $dates[$key];
					break;

				case 's':
					$ret['second'] = $dates[$key];
					break;
			}
		}

		return $ret;
	}
}
