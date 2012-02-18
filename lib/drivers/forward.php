<?php

/*
 +-----------------------------------------------------------------------+
 | lib/drivers/forward.php                                               |
 |                                                                       |
 | Copyright (C) 2012 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
 */

/*
 * Read driver function.
 *
 * @param array $data the array of data to get and set.
 *
 * @return integer the status code.
 */
function vacation_read(array &$data)
{
	$forward_filename = construct_forward_filename($data);
	
 	if (!is_file($forward_filename))
	{
		return PLUGIN_SUCCESS;
	}
	
	$forward_fd = fopen($forward_filename, "r");
	if (!$forward_fd)
	{			
		return PLUGIN_ERROR_PROCESS;
	}
	
	$line = fgets($forward_fd, 256);
	if (!$line)
	{		
		fclose($forward_fd);
			
		return PLUGIN_ERROR_PROCESS;
	}
		
	fclose($forward_fd);
	
	$vacation_forward = construct_vacation_forward($data);

	if ((strlen($line) == 0) && (strcmp($line, $vacation_forward) != 0))
		return PLUGIN_SUCCESS;

	$vacation_message_filename = construct_vacation_message_filename($data);
	$vacation_database_filename = construct_vacation_database_filename($data);
	
	if (!is_file($vacation_message_filename) || !is_file($vacation_database_filename))
	{
		return PLUGIN_SUCCESS;
	}
	
	$vacation_message = file_get_contents($vacation_message_filename);
	if (!$vacation_message)
	{
		return PLUGIN_PROCESS;
	}
		
	$data['vacation_enable'] = true;
	$data['vacation_subject'] = extract_subject_from_mail($vacation_message);
	$data['vacation_message'] = extract_message_from_mail($vacation_message);
	
	return PLUGIN_SUCCESS;
}

/*
 * Write driver function.
 *
 * @param array $data the array of data to get and set.
 *
 * @return integer the status code.
 */
function vacation_write(array &$data)
{
	$rcmail = rcmail::get_instance();
	
	$vacation_database_filename = construct_vacation_database_filename($data);
	$vacation_message_filename = construct_vacation_message_filename($data);
	$vacation_message_template = $rcmail->config->get('vacation_forward_message_template', '');
	$forward_filename = construct_forward_filename($data);
	$vacation_forward_command = $rcmail->config->get('vacation_forward_command', '/usr/bin/vacation');
	
	if ($data['vacation_enable'])
	{
		if (!shell_exec($vacation_forward_command . " -i -f " . $vacation_database_filename) == NULL)
		{
			return FALSE;
		}
		
		$mode = $rcmail->config->get('vacation_forward_database_file_mode', 0644);

		if (!chmod($vacation_database_filename, $mode))
		{
			return FALSE;
		}
		
		$vacation_message_fd = fopen($vacation_message_filename, "w+");
		if (!$vacation_message_fd)
		{
			$this->rc->output->command('display_message', $this->gettext('forwardopenerror'), 'error');
		
			return FALSE;
		}
		
		if (!fwrite($vacation_message_fd, construct_vacation_mail($data)))
		{
			$this->rc->output->command('display_message', $this->gettext('forwardwriteerror'), 'error');
		
			fclose($vacation_message_fd);
		
			return FALSE;
		}
		
		fclose($vacation_message_fd);
				
		$vacation_forward = construct_vacation_forward($data);

		$forward_fd = fopen($forward_filename, "w+");
		if (!$forward_fd)
		{
			$this->rc->output->command('display_message', $this->gettext('forwardopenerror'), 'error');
		
			return FALSE;
		}
		
		if (!fwrite($forward_fd, $vacation_forward))
		{
			$this->rc->output->command('display_message', $this->gettext('forwardwriteerror'), 'error');
		
			fclose($forward_fd);
		
			return FALSE;
		}
				
		fclose($forward_fd);
	}
	else
	{
		unlink($forward_filename);
		unlink($vacation_message_filename);
		unlink($vacation_database_filename);
		
		$data['vacation_subject'] = NULL;
		$data['vacation_message'] = NULL;
	}
	
	return PLUGIN_SUCCESS;
}

function construct_forward_filename(array $data)
{
	$rcmail = rcmail::get_instance();
	
 	$forward_path = $rcmail->config->get('vacation_forward_path', '/var/spool/forward/%email_local');
 	$forward_file = $rcmail->config->get('vacation_forward_file', '.forward');

	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email'
	);
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']
	);

	$forward_path = str_replace($search, $replace, $forward_path);
	$forward_file = str_replace($search, $replace, $forward_file);
	$filename = $forward_path . '/' . $forward_file;

	if ($rcmail->config->get('vacation_forward_create_dir', false))
	{
		$mode = $rcmail->config->get('vacation_forward_create_dir_mode', 0755);

		mkdir($forward_path, $mode, false);
	}

	return $filename;
}

function construct_vacation_message_filename(array $data)
{
	$rcmail = rcmail::get_instance();
	
	$forward_path = $rcmail->config->get('vacation_forward_path', '/var/spool/forward/%email_local');
	$vacation_file = $rcmail->config->get('vacation_forward_message_file', '.vacation.msg');

	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email'
	);
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']
	);

	$forward_path = str_replace($search, $replace, $forward_path);
	$vacation_file = str_replace($search, $replace, $vacation_file);
	$filename = $forward_path . '/' . $vacation_file;

	if ($rcmail->config->get('vacation_forward_create_dir', FALSE))
	{
		$mode = $rcmail->config->get('vacation_forward_create_dir_mode', 0755);

		mkdir($forward_path, $mode, false);
	}

	return $filename;
}

function construct_vacation_database_filename(array $data)
{
	$rcmail = rcmail::get_instance();
	
	$forward_path = $rcmail->config->get('vacation_forward_path', '/var/spool/forward/%email_local');
	$vacation_file = $rcmail->config->get('vacation_forward_database_file', '.vacation.db');

	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email'
	);
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']
	);

	$forward_path = str_replace($search, $replace, $forward_path);
	$vacation_file = str_replace($search, $replace, $vacation_file);
	$filename = $forward_path . '/' . $vacation_file;

	if ($rcmail->config->get('vacation_forward_create_dir', false))
	{
		$mode = $rcmail->config->get('vacation_forward_create_dir_mode', 0755);

		mkdir($forward_path, $mode, false);
	}

	return $filename;
}

function construct_vacation_forward(array $data)
{
 	$rcmail = rcmail::get_instance();
	
 	$forward_path = $rcmail->config->get('vacation_forward_path', '/var/spool/forward/%email_local');
	$forward_vacation_message_file = $rcmail->config->get('vacation_forward_message_file', '.vacation.msg');
	$forward_vacation_database_file = $rcmail->config->get('vacation_forward_database_file', '.vacation.db');
	$forward_vacation_command = $rcmail->config->get('vacation_forward_vacation_command', '/usr/bin/vacation');
	$forward_vacation_reply_interval = $rcmail->config->get('vacation_forward_vacation_reply_interval', 0);

	$forward = "\%email, \"|" . $forward_vacation_command . " " .
	        	"-a allman %email_local " .
	        	"-r " . $forward_vacation_reply_interval . " " .
	        	"-m " . $forward_path . "/" . $forward_vacation_message_file . " " .
	        	"-f " . $forward_path . "/" . $forward_vacation_database_file. "\"";

	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email'
	);
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']
	);

	$forward = str_replace($search, $replace, $forward);

	return $forward;
}

function construct_vacation_mail(array $data)
{
	$rcmail = rcmail::get_instance();
	
	$message = $rcmail->config->get('vacation_forward_message_template', '');
	
	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email',
					'%vacation_subject',
					'%vacation_message'
	);
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email'],
					 $data['vacation_subject'],
					 $data['vacation_message']
	);
	
	$message = str_replace($search, $replace, $message);
	
	return $message;
}

function extract_subject_from_mail($mail)
{
	$headers = strstr($mail, "\r\n\r\n", false);
	if ($headers == NULL)
		return NULL;

	foreach (explode("\r\n", $headers) as $header)
	{
		if (($str = strstr($header, "Subject: ")) != NULL)
			return strstr($str, "\r\n", false);
	}
	
	return NULL;
}

function extract_message_from_mail($mail)
{
	$str = strstr($mail, "\r\n\r\n");
	if ($str == NULL)
		return NULL;
	
	return substr($str, 4);
}
