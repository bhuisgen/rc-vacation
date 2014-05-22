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
		
		if ($this->rc->config->get('vacation_gui_vacationdate', FALSE) && $this->rc->config->get('vacation_jquery_calendar', FALSE))
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
		rcmail_overwrite_action('plugin.vacation');
		$this->rc->output->send('plugin');
	}

	/*
	 * Plugin UI form function.
	 */
	public function vacation_form()
	{
		$table = new html_table(array('cols' => 2));

		$field_id = 'vacationenable';
		$input_vacationenable = new html_checkbox(array('name' => '_vacationenable', 'id' => $field_id, 'value' => 1));
		$table->add('title', html::label($field_id, Q($this->gettext('vacationenable'))));
		$table->add(null, $input_vacationenable->show($this->obj->is_vacation_enable() ? 1 : 0));
		
		if ($this->rc->config->get('vacation_gui_vacationdate', FALSE))
		{
			$format = $this->rc->config->get('vacation_dateformat', 'm/d/Y');
			
			$field_id = 'vacationstart';
			$input_vacationstart = new html_inputfield(array('name' => '_vacationstart', 'id' => $field_id, 'size' => 10));
			$table->add('title', html::label($field_id, Q($this->gettext('vacationstart'))));
			$val = $this->obj->get_vacation_start();
			if ($val == 0)
			{
				$val = time();
			}
			$table->add(null, $input_vacationstart->show(date($format, $val)));

			$field_id = 'vacationend';
			$input_vacationend = new html_inputfield(array('name' => '_vacationend', 'id' => $field_id, 'size' => 10));
			$table->add('title', html::label($field_id, Q($this->gettext('vacationend'))));
			$val = $this->obj->get_vacation_end();
			if ($val == 0)
			{
				$val = time();
			}
			$table->add(null, $input_vacationend->show(date($format, $val)));
		}

		if ($this->rc->config->get('vacation_gui_vacationsubject', FALSE))
		{
			$field_id = 'vacationsubject';
			$input_vacationsubject = new html_inputfield(array('name' => '_vacationsubject', 'id' => $field_id, 'size' => 40));
			$table->add('title', html::label($field_id, Q($this->gettext('vacationsubject'))));
			$table->add(null, $input_vacationsubject->show($this->obj->get_vacation_subject()));
		}

		$field_id = 'vacationmessage';
		if ($this->rc->config->get('vacation_gui_vacationmessage_html', FALSE))
		{
			$this->rc->output->add_label('converting', 'editorwarning');
			// FIX: use identity mode for minimal functions
			rcube_html_editor('identity');

			$text_vacationmessage = new html_textarea(array('name' => '_vacationmessage', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 6, 'cols' => 40, 'class' => 'mce_editor'));
		}
		else
		{
			$text_vacationmessage = new html_textarea(array('name' => '_vacationmessage', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 6, 'cols' => 40));
		}

		$table->add('title', html::label($field_id, Q($this->gettext('vacationmessage'))));
		$table->add(null, $text_vacationmessage->show($this->obj->get_vacation_message()));

		if ($this->rc->config->get('vacation_gui_vacationkeepcopyininbox', FALSE))
		{
			$field_id = 'keepcopyininbox';
			$input_vacationkeepcopyininbox = new html_checkbox(array('name' => '_vacationkeepcopyininbox', 'id' => $field_id, 'value' => 1));
			$table->add('title', html::label($field_id, Q($this->gettext('vacationkeepcopyininbox'))));
			$table->add(null, $input_vacationkeepcopyininbox->show($this->obj->is_vacation_keep_copy_in_inbox() ? 1 : 0));
		}

		if ($this->rc->config->get('vacation_gui_vacationforwarder', FALSE))
		{
			$field_id = 'vacationforwarder';
			$input_vacationforwarder = new html_inputfield(array('name' => '_vacationforwarder', 'id' => $field_id, 'size' => 20));
			$table->add('title', html::label($field_id, Q($this->gettext('vacationforwarder'))));
			$table->add(null, $input_vacationforwarder->show($this->obj->get_vacation_forwarder()));
		}

		$out = html::div(array('class' => "box"), html::div(array('id' => "prefs-title", 'class' => 'boxtitle'), $this->gettext('vacation')) . html::div(array('class' => "boxcontent"), $table->show() . html::p(null, $this->rc->output->button(array('command' => 'plugin.vacation-save', 'type' => 'input', 'class' => 'button mainaction', 'label' => 'save')))));

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

					return FALSE;
				}

			case PLUGIN_ERROR_CONNECT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverconnecterror'), 'error');

					return FALSE;
				}

			case PLUGIN_ERROR_PROCESS:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverprocesserror'), 'error');

					return FALSE;
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

		return TRUE;
	}

	/*
	 * Writes plugin data.
	 */
	public function write_data()
	{
		if (get_input_value('_vacationenable', RCUBE_INPUT_POST))
		{
			$this->obj->set_vacation_enable(TRUE);
		}
		else
		{
			$this->obj->set_vacation_enable(FALSE);
		}

		if ($this->rc->config->get('vacation_gui_vacationdate', FALSE))
		{
			$date_start = get_input_value('_vacationstart', RCUBE_INPUT_POST);
			$d_start = $this->my_date_parse_from_format($this->rc->config->get('vacation_dateformat', 'm/d/Y'), $date_start);
			if (!is_array($d_start) || !isset($d_start['month']) || !isset($d_start['day']) || !isset($d_start['year']))
			{
				$this->rc->output->command('display_message', $this->gettext('vacationinvalidstartdate'), 'error');
				
				return FALSE;
			}

			$date_end = get_input_value('_vacationend', RCUBE_INPUT_POST);
			$d_end = $this->my_date_parse_from_format($this->rc->config->get('vacation_dateformat', 'm/d/Y'), $date_end);
			if (!is_array($d_end) || !isset($d_end['month']) || !isset($d_end['day']) || !isset($d_end['year']))
			{
				$this->rc->output->command('display_message', $this->gettext('vacationinvalidenddate'), 'error');
				
				return FALSE;
			}

			$d_start_time = mktime(0,0,0, $d_start['month'], $d_start['day'], $d_start['year']);
			$d_end_time = mktime(0,0,0, $d_end['month'], $d_end['day'], $d_end['year']); 
			if ($d_start_time > $d_end_time)
			{
				$this->rc->output->command('display_message', $this->gettext('vacationinvaliddateinterval'), 'error');

				return FALSE;
			}

			$this->obj->set_vacation_start(gmmktime(0, 0, 0, $d_start['month'], $d_start['day'], $d_start['year']));
			$this->obj->set_vacation_end(gmmktime(0, 0, 0, $d_end['month'], $d_end['day'], $d_end['year']));
		}
		
		if ($this->rc->config->get('vacation_gui_vacationsubject', FALSE))
		{
			$subject = get_input_value('_vacationsubject', RCUBE_INPUT_POST);
			if (!is_string($subject) || (strlen($subject) == 0))
			{
				$this->rc->output->command('display_message', $this->gettext('vacationnosubject'), 'error');
				
				return FALSE;
			}
			
			$this->obj->set_vacation_subject($subject);
		}
		
		$message = get_input_value('_vacationmessage', RCUBE_INPUT_POST, $this->rc->config->get('vacation_gui_vacationmessage_html', FALSE));
		if (!is_string($message) || (strlen($message) == 0))
		{
			$this->rc->output->command('display_message', $this->gettext('vacationnomessage'), 'error');
				
			return FALSE;
		}
		$this->obj->set_vacation_message($message);
		
		if ($this->rc->config->get('vacation_gui_keepcopyininbox', FALSE))
		{
			if (get_input_value('_vacationkeepcopyininbox', RCUBE_INPUT_POST))
			{
				$this->obj->set_vacation_keep_copy_in_inbox(TRUE);
			}
			else
			{
				$this->obj->set_vacation_keep_copy_in_inbox(FALSE);
			}
		}

		if ($this->rc->config->get('vacation_gui_vacationforwarder', FALSE))
		{
			$forwarder = get_input_value('_vacationforwarder', RCUBE_INPUT_POST);
			if (is_string($forwarder) && (strlen($forwarder) > 0))
			{
				if ($this->rc->config->get('vacation_forwarder_multiple', FALSE))
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
						if ($this->rc->config->get('vacation_forwarder_multiple', FALSE))
							$this->rc->output->command('display_message', $this->gettext('vacationinvalidforwarders'), 'error');
						else
							$this->rc->output->command('display_message', $this->gettext('vacationinvalidforwarder'), 'error');
						
						return FALSE;
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
		
		$ret = vacation_write ($data);
		switch ($ret)
		{
			case PLUGIN_ERROR_DEFAULT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverdefaulterror'), 'error');

					return FALSE;
				}

			case PLUGIN_ERROR_CONNECT:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverconnecterror'), 'error');

					return FALSE;
				}

			case PLUGIN_ERROR_PROCESS:
				{
					$this->rc->output->command('display_message', $this->gettext('vacationdriverprocesserror'), 'error');

					return FALSE;
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
			$this->obj->set_vacation_start($data['vacation_start']);
		}

		if (isset($data['vacation_end']))
		{
			$this->obj->set_vacation_end($data['vacation_end']);
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

		return TRUE;
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
