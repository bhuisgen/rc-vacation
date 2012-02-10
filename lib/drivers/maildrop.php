<?php

/*
 +-----------------------------------------------------------------------+
 | lib/drivers/maildrop.php                                              |
 |                                                                       |
 | Copyright (C) 2010 Sylvain LANGLADE                                   |
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
	$rcmail = rcmail::get_instance();

	// Storage path
	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email',
					'%vacation_enable');
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email'],
					 $data['vacation_enable'] ? 'enabled' : 'disabled');
					 
	$path = str_replace($search, $replace, $rcmail->config->get('vacation_maildrop_maildirpath'));
	if (substr($path, -1, 1) != '/')
		$path .= '/';

	// Does the path exists ?
	if (!is_dir($path))
		return PLUGIN_ERROR_PROCESS;

	// Test whether the 'enabled' or 'disabled' vacation message file exists
	$file = str_replace($search, $replace, $rcmail->config->get('vacation_maildrop_enabled'));
	if (is_readable($path.$file))
	{
		$data['vacation_enable'] = true;	// The 'enabled' file exists
		$data['vacation_message'] = file_get_contents($path.$file);
	}
	else
	{
		$file = $rcmail->config->get('vacation_maildrop_disabled');
		if (is_readable($path.$file))
		{
			$data['vacation_enable'] = false;	// The 'disabled' file exists
			$data['vacation_message'] = file_get_contents($path.$file);
		}
		else
		{
			$data['vacation_enable'] = false;	// No file at all, assuming 'disabled'
			$data['vacation_message'] = "";
		}
	}

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

	// Storage path
	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email',
					'%vacation_enable');
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email'],
					 $data['vacation_enable'] ? $rcmail->config->get('vacation_maildrop_vactionenable_value_enabled') : $rcmail->config->get('vacation_maildrop_vacationenable_value_disabled'));
					 
	$path = str_replace($search, $replace, $rcmail->config->get('vacation_maildrop_maildirpath'));
	if (substr($path, -1, 1) != '/')
		$path .= '/';

	// Does the path exists ?
	if (!is_dir($path))
		return PLUGIN_ERROR_PROCESS;

	// Whether the user enabled the vacation message, choose the right filename to delete and create
	if ($data['vacation_enable'] && ($data['vacation_message'] > ""))
	{
		$fdelete = str_replace($search, $replace, $path.$rcmail->config->get('vacation_maildrop_disabled'));
		$fcreate = str_replace($search, $replace, $path.$rcmail->config->get('vacation_maildrop_enabled'));
	}
	else
	{
		$fdelete = str_replace($search, $replace, $path.$rcmail->config->get('vacation_maildrop_enabled'));
		$fcreate = str_replace($search, $replace, $path.$rcmail->config->get('vacation_maildrop_disabled'));
	}

	// Delete the correct filename (if it exists)
	if (is_readable($fdelete))
	{
		if (is_writeable($fdelete))
			unlink($fdelete);
		else
			return PLUGIN_ERROR_CONNECT;
	}

	// Create the vacation message, using the correct filename
	if (file_put_contents($fcreate, $data['vacation_message']) === false)
		return PLUGIN_ERROR_DEFAULT;

	return PLUGIN_SUCCESS;
}
