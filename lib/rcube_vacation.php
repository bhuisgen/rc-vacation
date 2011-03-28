<?php

/*
 +-----------------------------------------------------------------------+
 | lib/rcube_vacation.php                                                |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
 */

class rcube_vacation
{
	public $username = '';
	public $email = '';
	public $email_local = '';
	public $email_domain =  '';
	public $vacation_enable = FALSE;
	public $vacation_start = 0;
	public $vacation_end = 0;
	public $vacation_subject = '';
	public $vacation_message = '';
	public $vacation_keepcopyininbox = TRUE;
	public $vacation_forwarder = '';

	/**
	 * Constructor of the class.
	 */
	public function __construct()
	{
		$this->init();
	}
	
	/*
	 * Initialize the object.
	 */
	private function init()
	{
		$this->username = rcmail::get_instance()->user->get_username();
		
	    $parts = explode('@', $this->username);
	    if (count($parts) >= 2)
	    {
	       $this->email = $this->username;
	       $this->email_local = $parts[0];
	       $this->email_domain = $parts[1] ;
	    }
	}
	
	/*
	 * Gets the username.
	 *
	 * @return string the username.
	 */		
	public function get_username()
	{
		return $this->username;
	}
	
	/*
	 * Gets the full email of the user.
	 *
	 * @return string the email of the user.
	 */			
	public function get_email()
	{	    
	    return $this->email;
    }
	
	/*
	 * Gets the email local part of the user.
	 *
	 * @return string the email local part.
	 */		
	public function get_email_local()
	{    
	    return $this->email_local;
    }

	/*
	 * Gets the email domain of the user.
	 *
	 * @return string the email domain.
	 */			
	public function get_email_domain()
	{	    
	    return $this->email_domain;
    }

	/*
	 * Checks if the vacation is enabled.
	 *
	 * @return boolean TRUE if vacation is enabled; FALSE otherwise.
	 */
	public function is_vacation_enable ()
	{
		return $this->vacation_enable;
	}

	/*
	 * Gets the vacation start date.
	 *
	 * @returng int the timestamp of the start date.
	 */
	public function get_vacation_start()
	{
		return $this->vacation_start;
	}

	/*
	 * Gets the vacation end date.
	 *
	 * @returng int the timestamp of the end date.
	 */
	public function get_vacation_end()
	{
		return $this->vacation_end;
	}
	
	/*
	 * Gets the vacation subject.
	 *
	 * @return string the vacation subject.
	 */
	public function get_vacation_subject()
	{
		return $this->vacation_subject;
	}

	/*
	 * Gets the vacation message.
	 *
	 * @return string the vacation message.
	 */
	public function get_vacation_message()
	{
		return $this->vacation_message;
	}
	
	/*
	 * Checks if a copy in inbox must be keep when the vacation is enabled.
	 *
	 * @return boolean TRUE if a copy must be keeped; FALSE otherwise.
	 */
	public function is_vacation_keep_copy_in_inbox()
	{
		return $this->vacation_keepcopyininbox;
	}
	
	/*
	 * Gets the vacation forward address.
	 * 
	 * @return string the vacation forward address.
	 */
	public function get_vacation_forwarder()
	{
		return $this->vacation_forwarder;
	}

	/*
	 * Sets the email of the user
	 *
	 * @param string $email the email.
	 */
	public function set_email($email)
	{
		$this->email = $email;
	}
	
	/*
	 * Sets the email local part of the user
	 *
	 * @param string $local the local part of the email.
	 */
	public function set_email_local($local)
	{
		$this->email_local = $local;
	}
	
	/*
	 * Sets the email domain part of the user
	 *
	 * @param string $local the domain part of the email.
	 */
	public function set_email_domain($domain)
	{
		$this->email_domain = $domain;
	}
	
	/*
	 * Enables or disables the vacation.
	 *
	 * @param boolean the flag.
	 */
	public function set_vacation_enable($flag)
	{
		$this->vacation_enable = $flag;
	}

	/*
	 * Sets the vacation start date.
	 *
	 * @param int the timestamp of the vacation start date.
	 */
	public function set_vacation_start ($date)
	{
		$this->vacation_start = $date;
	}

	/*
	 * Sets the vacation end date.
	 *
	 * @param int the timestamp of the vacation end date.
	 */
	public function set_vacation_end ($date)
	{
		$this->vacation_end = $date;
	}
	
	/*
	 * Sets the vacation subject.
	 *
	 * @param string $subject the vacation subject.
	 */
	public function set_vacation_subject($subject)
	{
		$this->vacation_subject = $subject;
	}

	/*
	 * Sets the vacation message.
	 *
	 * @param string $message the vacation message.
	 */
	public function set_vacation_message($message)
	{
		$this->vacation_message = $message;
	}
	
	/*
	 * Sets the vacation keep copy in inbox flag.
	 *
	 * @param boolean the flag.
	 */
	public function set_vacation_keep_copy_in_inbox($flag)
	{
		$this->vacation_keepcopyininbox = $flag;
	}
	
	/*
	 * Sets the vacation forward address.
	 * 
	 * @param string $forwarder the vacation forward address.
	 */
	public function set_vacation_forwarder($forwarder)
	{
		$this->vacation_forwarder = $forwarder;
	}
}
