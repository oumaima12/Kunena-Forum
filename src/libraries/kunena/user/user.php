<?php
/**
 * Kunena Component
 * @package         Kunena.Framework
 * @subpackage      User
 *
 * @copyright       Copyright (C) 2008 - 2018 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/
defined('_JEXEC') or die();

jimport('joomla.utilities.date');

/**
 * Class KunenaUser
 *
 * @property    int    $userid
 * @property    int    $status
 * @property    string $status_text
 * @property    string $name
 * @property    string $username
 * @property    string $email
 * @property    int    $blocked
 * @property    string $registerDate
 * @property    string $lastvisitDate
 * @property    string $signature
 * @property    int    $moderator
 * @property    int    $banned
 * @property    int    $ordering
 * @property    int    $posts
 * @property    string $avatar
 * @property    int    $karma
 * @property    int    $karma_time
 * @property    int    $uhits
 * @property    string $personalText
 * @property    int    $gender
 * @property    string $birthdate
 * @property    string $location
 * @property    string $websitename
 * @property    string $websiteurl
 * @property    int    $rank
 * @property    int    $view
 * @property    int    $hideEmail
 * @property    int    $showOnline
 * @property    int    $canSubscribe
 * @property    int    $userListtime
 * @property    string $icq
 * @property    string $yim
 * @property    string $microsoft
 * @property    string $skype
 * @property    string $twitter
 * @property    string $facebook
 * @property    string $google
 * @property    string $myspace
 * @property    string $linkedin
 * @property    string $delicious
 * @property    string $friendfeed
 * @property    string $digg
 * @property    string $blogspot
 * @property    string $flickr
 * @property    string $bebo
 * @property    int    $thankyou
 * @property    string $instagram
 * @property    string $qq
 * @property    string $qzone
 * @property    string $weibo
 * @property    string $wechat
 * @property    string $apple
 * @property    string $vk
 * @property    string $telegram
 * @property    string $whatsapp
 * @property    string $youtube
 * @property    string $ok
 * @since Kunena
 */
class KunenaUser extends JObject
{
	/**
	 * @var null
	 * @since Kunena
	 */
	protected static $_ranks = null;

	/**
	 * @var null
	 * @since Kunena
	 */
	protected $_allowed = null;

	/**
	 * @var array
	 * @since Kunena
	 */
	protected $_link = array();

	/**
	 * @var mixed
	 * @since Kunena
	 */
	protected $_time;

	/**
	 * @var mixed
	 * @since Kunena
	 */
	protected $_pm;

	/**
	 * @var string
	 * @since Kunena
	 */
	protected $_email;

	/**
	 * @var string
	 * @since Kunena
	 */
	protected $_website;

	/**
	 * @var string
	 * @since Kunena
	 */
	protected $_personalText;

	/**
	 * @var string
	 * @since Kunena
	 */
	protected $_signature;

	/**
	 * @var boolean
	 * @since Kunena
	 */
	protected $_exists = false;

	/**
	 * @var JDatabaseDriver|null
	 * @since Kunena
	 */
	protected $_db = null;

	/**
	 * @param   int $identifier identifier
	 *
	 * @throws Exception
	 * @internal
	 * @since Kunena
	 */
	public function __construct($identifier = 0)
	{
		// Always load the user -- if user does not exist: fill empty data
		if ($identifier !== false)
		{
			$this->load($identifier);
		}

		if (!isset($this->userid))
		{
			$this->userid = 0;
		}

		$this->_db     = \Joomla\CMS\Factory::getDBO();
		$this->_app    = \Joomla\CMS\Factory::getApplication();
		$this->_config = KunenaFactory::getConfig();
	}

	/**
	 * Method to load a KunenaUser object by userid.
	 *
	 * @param   mixed $id The user id of the user to load.
	 *
	 * @return    boolean            True on success
	 * @since Kunena
	 */
	public function load($id)
	{
		// Create the user table object
		$table = $this->getTable();

		// Load the KunenaTableUser object based on the user id
		if ($id > 0)
		{
			$this->_exists = $table->load($id);
		}

		// Assuming all is well at this point lets bind the data
		$this->setProperties($table->getProperties());

		// Set showOnline if user doesn't exists (if we will save the user)
		if (!$this->_exists)
		{
			$this->showOnline = 1;
		}

		return $this->_exists;
	}

	/**
	 * Method to get the user table object.
	 *
	 * @param   string $type   The user table name to be used.
	 * @param   string $prefix The user table prefix to be used.
	 *
	 * @return    \Joomla\CMS\Table\Table|TableKunenaUsers    The user table object.
	 * @since Kunena
	 */
	public function getTable($type = 'KunenaUsers', $prefix = 'Table')
	{
		static $tabletype = null;

		// Set a custom table type is defined
		if ($tabletype === null || $type != $tabletype ['name'] || $prefix != $tabletype ['prefix'])
		{
			$tabletype ['name']   = $type;
			$tabletype ['prefix'] = $prefix;
		}

		// Create the user table object
		return \Joomla\CMS\Table\Table::getInstance($tabletype ['name'], $tabletype ['prefix']);
	}

	/**
	 * Returns the global KunenaUser object, only creating it if it doesn't already exist.
	 *
	 * @param   null|int $identifier The user to load - Can be an integer or string - If string, it is converted to ID
	 *                               automatically.
	 * @param   bool     $reload     Reload user from database.
	 *
	 * @return KunenaUser
	 * @throws Exception
	 * @since Kunena
	 */
	public static function getInstance($identifier = null, $reload = false)
	{
		return KunenaUserHelper::get($identifier, $reload);
	}

	/**
	 * Returns true if user is authorised to do the action.
	 *
	 * @param   string     $action action
	 * @param   KunenaUser $user   user
	 *
	 * @return boolean
	 *
	 * @throws null
	 * @since  K4.0
	 */
	public function isAuthorised($action = 'read', KunenaUser $user = null)
	{
		return !$this->tryAuthorise($action, $user, false);
	}

	/**
	 * Throws an exception if user isn't authorised to do the action.
	 *
	 * @param   string     $action action
	 * @param   KunenaUser $user   user
	 * @param   bool       $throw  throw
	 *
	 * @return KunenaExceptionAuthorise|null
	 * @throws null
	 * @since  K4.0
	 */
	public function tryAuthorise($action = 'read', KunenaUser $user = null, $throw = true)
	{
		// Special case to ignore authorisation.
		if ($action == 'none')
		{
			return false;
		}

		// Load user if not given.
		if ($user === null)
		{
			$user = KunenaUserHelper::getMyself();
		}

		$input     = \Joomla\CMS\Factory::getApplication()->input;
		$method    = $input->getInt('userid');
		$kuser     = KunenaFactory::getUser($method);
		$config    = KunenaConfig::getInstance();
		$exception = null;

		switch ($action)
		{
			case 'read' :
				if (!isset($this->registerDate) || (!$user->exists() && !$config->pubprofile))
				{
					$exception = new KunenaExceptionAuthorise(JText::_('COM_KUNENA_PROFILEPAGE_NOT_ALLOWED_FOR_GUESTS'), $user->exists() ? 403 : 404);
				}
				break;
			case 'edit' :
				if (!isset($this->registerDate) || !$this->isMyself() && !$user->isAdmin() && !$user->isModerator())
				{
					$exception = new KunenaExceptionAuthorise(JText::sprintf('COM_KUNENA_VIEW_USER_EDIT_AUTH_FAILED', $this->getName()), $user->exists() ? 403 : 401);
				}

				if ($user->isModerator() && $kuser->isAdmin() && !$user->isAdmin())
				{
					$exception = new KunenaExceptionAuthorise(JText::sprintf('COM_KUNENA_VIEW_USER_EDIT_AUTH_FAILED', $this->getName()), $user->exists() ? 403 : 401);
				}
				break;
			case 'ban' :
				$banInfo = KunenaUserBan::getInstanceByUserid($this->userid, true);

				try
				{
					$banInfo->canBan();
				}
				catch (Exception $e)
				{
					$exception = new KunenaExceptionAuthorise($e->getMessage(), $user->exists() ? 403 : 401);
				}
				break;
			default :
				throw new InvalidArgumentException(JText::sprintf('COM_KUNENA_LIB_AUTHORISE_INVALID_ACTION', $action), 500);
		}

		// Throw or return the exception.
		if ($throw && $exception)
		{
			throw $exception;
		}

		return $exception;
	}

	/**
	 * @param   null|bool $exists exists
	 *
	 * @return boolean
	 * @since Kunena
	 */
	public function exists($exists = null)
	{
		$return = $this->_exists;

		if ($exists !== null)
		{
			$this->_exists = $exists;
		}

		return $return;
	}

	/**
	 * Is the user me?
	 *
	 * @return boolean
	 * @since Kunena
	 */
	public function isMyself()
	{
		$result = KunenaUserHelper::getMyself()->userid == $this->userid;

		return $result;
	}

	/**
	 * Checks if user has administrator permissions in the category.
	 *
	 * If no category is given or it doesn't exist, check will be done against global administrator permissions.
	 *
	 * @param   KunenaForumCategory $category category
	 *
	 * @return boolean
	 * @throws Exception
	 * @since Kunena
	 */
	public function isAdmin(KunenaForumCategory $category = null)
	{
		return KunenaAccess::getInstance()->isAdmin($this, $category && $category->exists() ? $category->id : null);
	}

	/**
	 * Checks if user has moderator permissions in the category.
	 *
	 * If no category is given or it doesn't exist, check will be done against global moderator permissions.
	 *
	 * @param   KunenaForumCategory $category category
	 *
	 * @return boolean
	 * @throws Exception
	 * @since Kunena
	 */
	public function isModerator(KunenaForumCategory $category = null)
	{
		return KunenaAccess::getInstance()->isModerator($this, $category && $category->exists() ? $category->id : null);
	}

	/**
	 * @param   string $visitorname visitor name
	 * @param   bool   $escape      escape
	 *
	 * @return string
	 * @since Kunena
	 */
	public function getName($visitorname = '', $escape = true)
	{
		if (!$this->userid && !$this->name)
		{
			$name = $visitorname;
		}
		else
		{
			$usersConfig = \Joomla\CMS\Plugin\PluginHelper::isEnabled('kunena', 'comprofiler');

			if ($usersConfig)
			{
				global $ueConfig;

				if ($ueConfig['name_format'] == 1)
				{
					return $this->name;
				}
				elseif ($ueConfig['name_format'] == 2)
				{
					return $this->name . ' (' . $this->username . ')';
				}
				elseif ($ueConfig['name_format'] == 3)
				{
					return $this->username;
				}
				elseif ($ueConfig['name_format'] == 4)
				{
					return $this->username . ' (' . $this->name . ')';
				}
			}
			else
			{
				$name = $this->_config->username ? $this->username : $this->name;
			}
		}

		if ($escape)
		{
			$name = htmlspecialchars($name, ENT_COMPAT, 'UTF-8');
		}

		return $name;
	}

	/**
	 * @param   mixed $data   data
	 * @param   array $ignore ignore
	 *
	 * @since Kunena
	 * @return void
	 */
	public function bind($data, array $ignore = array())
	{
		$data = array_diff_key($data, array_flip($ignore));
		$this->setProperties($data);
	}

	/**
	 * Method to delete the KunenaUser object from the database.
	 *
	 * @return    boolean    True on success.
	 * @throws Exception
	 * @since Kunena
	 */
	public function delete()
	{
		// Delete user table object
		$table = $this->getTable();

		$result = $table->delete($this->userid);

		if (!$result)
		{
			$this->setError($table->getError());
		}

		$access = KunenaAccess::getInstance();
		$access->clearCache();

		return $result;

	}

	/**
	 * @return integer
	 * @throws Exception
	 * @since Kunena
	 */
	public function getStatus()
	{
		return KunenaUserHelper::getStatus($this->userid);
	}

	/**
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getStatusText()
	{
		return KunenaHtmlParser::parseText($this->status_text);
	}

	/**
	 * @return array
	 * @throws Exception
	 * @since Kunena
	 */
	public function getAllowedCategories()
	{
		if (!isset($this->_allowed))
		{
			$this->_allowed = KunenaAccess::getInstance()->getAllowedCategories($this->userid);
		}

		return $this->_allowed;
	}

	/**
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getMessageOrdering()
	{
		static $default;

		if (is_null($default))
		{
			$default = KunenaFactory::getConfig()->get('default_sort') == 'desc' ? 'desc' : 'asc';
		}

		if ($this->exists())
		{
			return $this->ordering != '0' ? ($this->ordering == '1' ? 'desc' : 'asc') : $default;
		}
		else
		{
			return $default == 'asc' ? 'asc' : 'desc';
		}
	}

	/**
	 * @param   string     $class class
	 * @param   string|int $sizex sizex
	 * @param   int        $sizey sizey
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getAvatarImage($class = '', $sizex = 'thumb', $sizey = 90)
	{
		if (!$this->avatar && KunenaConfig::getInstance()->avatar_type)
		{
			$ktemplate     = KunenaFactory::getTemplate();
			$topicicontype = $ktemplate->params->get('topicicontype');

			if ($sizex == 20)
			{
				if ($topicicontype == 'fa')
				{
					return '<i class="fas fa-user-circle" aria-hidden="true"></i>';
				}

				if ($topicicontype == 'B2')
				{
					return '<span class="icon icon-user user-circle user-default" aria-hidden="true"></span>';
				}

				if ($topicicontype == 'B3')
				{
					return '<span class="glyphicon glyphicon-user user-circle user-default" aria-hidden="true"></span>';
				}
			}
			elseif ($sizex == 'logout' || $sizex == 'profile')
			{
				if ($topicicontype == 'fa')
				{
					return '<i class="fas fa-user-circle fa-7x"></i>';
				}

				if ($topicicontype == 'B2')
				{
					return '<span class="icon icon-user user-circle user-xl b2-7x" aria-hidden="true"></span>';
				}

				if ($topicicontype == 'B3')
				{
					return '<span class="glyphicon glyphicon-user user-circle user-xl b2-7x" aria-hidden="true"></span>';
				}
			}

			if ($topicicontype == 'fa')
			{
				return '<i class="fas fa-user-circle fa-3x"></i>';
			}

			if ($topicicontype == 'B2')
			{
				return '<span class="icon icon-user user-circle user-default" aria-hidden="true"></span>';
			}

			if ($topicicontype == 'B3')
			{
				return '<span class="glyphicon glyphicon-user user-circle user-default" aria-hidden="true"></span>';
			}
		}

		$avatars = KunenaFactory::getAvatarIntegration();

		return $avatars->getLink($this, $class, $sizex, $sizey);
	}

	/**
	 * @param   string|int $sizex sizex
	 * @param   int        $sizey sizey
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getAvatarURL($sizex = 'thumb', $sizey = 90)
	{
		$avatars = KunenaFactory::getAvatarIntegration();

		return $avatars->getURL($this, $sizex, $sizey);
	}

	/**
	 * Get users type as a string inside the specified category.
	 *
	 * @param   null $name  name
	 * @param   null $title title
	 * @param   null $class class
	 *
	 * @return string
	 * @throws Exception
	 * @internal param int $catid Category id or 0 for global.
	 * @internal param bool $code True if we want to return the code, otherwise return translation key.
	 *
	 * @since    K5.1.0
	 */
	public function getLinkNoStyle($name = null, $title = null, $class = null)
	{
		$optional_username = KunenaFactory::getTemplate()->params->get('optional_username');

		if ($optional_username == 0 || !$this->userid)
		{
			return false;
		}

		if (!$name)
		{
			if ($optional_username == 1)
			{
				$name = $this->username;
			}
			elseif ($optional_username == 2)
			{
				$name = $this->name;
			}
		}

		$key = "{$name}.{$title}";

		if (empty($this->_link[$key]))
		{
			if (!$title)
			{
				$title = JText::sprintf('COM_KUNENA_VIEW_USER_LINK_TITLE', $this->getName());
			}

			$link = $this->getURL();

			if (!empty($link))
			{
				$this->_link[$key] = "<a class=\"{$class}\" href=\"{$link}\" title=\"{$title}\">{$name}</a>";
			}
			else
			{
				$this->_link[$key] = "<span class=\"{$class}\">{$name}</span>";
			}
		}

		return $this->_link[$key];
	}

	/**
	 * @param   bool   $xhtml xhtml
	 * @param   string $task  task
	 *
	 * @return mixed
	 * @throws Exception
	 * @since Kunena
	 */
	public function getURL($xhtml = true, $task = '')
	{
		// Note: We want to link also existing users who have never visited Kunena before.
		if (!$this->userid || !$this->registerDate)
		{
			return;
		}

		$config = KunenaConfig::getInstance();
		$me     = KunenaUserHelper::getMyself();

		if (!$config->pubprofile && !$me->exists())
		{
			return false;
		}

		return KunenaFactory::getProfile()->getProfileURL($this->userid, $task, $xhtml);
	}

	/**
	 * Return local time for the user.
	 *
	 * @return KunenaDate  User time instance.
	 * @throws Exception
	 * @since Kunena
	 */
	public function getTime()
	{
		if (!isset($this->_time))
		{
			$timezone = \Joomla\CMS\Factory::getApplication()->get('offset', null);

			if ($this->userid)
			{
				$user     = \Joomla\CMS\User\User::getInstance($this->userid);
				$timezone = $user->getParam('timezone', $timezone);
			}

			$this->_time = new KunenaDate('now', $timezone);

			try
			{
				$offset = new DateTimeZone($timezone);
				$this->_time->setTimezone($offset);
			}
			catch (Exception $e)
			{
				// TODO: log error?
			}
		}

		return $this->_time;
	}

	/**
	 * Return registration date.
	 *
	 * @return KunenaDate
	 * @since Kunena
	 */
	public function getRegisterDate()
	{
		return KunenaDate::getInstance($this->registerDate);
	}

	/**
	 * Return last visit date.
	 *
	 * @return KunenaDate
	 * @since Kunena
	 */
	public function getLastVisitDate()
	{
		if (!$this->lastvisitDate || $this->lastvisitDate == "0000-00-00 00:00:00")
		{
			$date = KunenaDate::getInstance($this->registerDate);
		}
		else
		{
			$date = KunenaDate::getInstance($this->lastvisitDate);
		}

		return $date;
	}

	/**
	 * @param   string $layout layout
	 *
	 * @throws Exception
	 * @since Kunena
	 * @return void
	 */
	public function setTopicLayout($layout = 'default')
	{
		if ($layout != 'default')
		{
			$layout = $this->getTopicLayout($layout);
		}

		$this->_app->setUserState('com_kunena.topic_layout', $layout);

		if ($this->userid && $this->view != $layout)
		{
			$this->view = $layout;
			$this->save(true);
		}
	}

	/**
	 * @param   null|string $layout layout
	 *
	 * @return string
	 * @since Kunena
	 */
	public function getTopicLayout($layout = null)
	{
		if ($layout == 'default')
		{
			$layout = null;
		}

		if (!$layout)
		{
			$layout = $this->_app->getUserState('com_kunena.topic_layout');
		}

		if (!$layout)
		{
			$layout = $this->view;
		}

		switch ($layout)
		{
			case 'flat':
			case 'threaded':
			case 'indented':
				break;
			default:
				$layout = $this->_config->topic_layout;
		}

		return $layout;
	}

	/**
	 * Method to save the KunenaUser object to the database.
	 *
	 * @param   boolean $updateOnly Save the object only if not a new user.
	 *
	 * @return    boolean True on success.
	 * @throws Exception
	 * @since Kunena
	 */
	public function save($updateOnly = false)
	{
		// Create the user table object
		$table  = $this->getTable();
		$ignore = array('name', 'username', 'email', 'blocked', 'registerDate', 'lastvisitDate');
		$table->bind($this->getProperties(), $ignore);
		$table->exists($this->_exists);

		// Check and store the object.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Are we creating a new user
		$isnew = !$this->_exists;

		// If we aren't allowed to create new users return
		if (!$this->userid || ($isnew && $updateOnly))
		{
			return true;
		}

		// Store the user data in the database
		if (!$result = $table->store())
		{
			$this->setError($table->getError());
		}

		$access = KunenaAccess::getInstance();
		$access->clearCache();

		// Set the id for the KunenaUser object in case we created a new user.
		if ($result && $isnew)
		{
			$this->load($table->get('userid'));

			// Self::$_instances [$table->get ( 'id' )] = $this;
		}

		return $result;
	}

	/**
	 * Get the URL to private messages
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getPrivateMsgURL()
	{
		$private = KunenaFactory::getPrivateMessaging();

		return $private->getInboxURL();
	}

	/**
	 * Get the label for URL to private messages
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getPrivateMsgLabel()
	{
		$private = KunenaFactory::getPrivateMessaging();

		if ($this->isMyself())
		{
			$count = $private->getUnreadCount($this->userid);

			if ($count)
			{
				return JText::sprintf('COM_KUNENA_PMS_INBOX_NEW', $count);
			}
			else
			{
				return JText::_('COM_KUNENA_PMS_INBOX');
			}
		}
		else
		{
			return JText::_('COM_KUNENA_PM_WRITE');
		}
	}

	/**
	 * Get link to private messages.
	 *
	 * @return string  URL.
	 *
	 * @throws Exception
	 * @since  K4.0
	 */
	public function getPrivateMsgLink()
	{
		if (!isset($this->_pm))
		{
			$private = KunenaFactory::getPrivateMessaging();

			if (!$this->userid)
			{
				$this->_pm = '';
			}
			elseif ($this->isMyself())
			{
				$count     = $private->getUnreadCount($this->userid);
				$this->_pm = $private->getInboxLink($count
					? JText::sprintf('COM_KUNENA_PMS_INBOX_NEW', $count)
					: JText::_('COM_KUNENA_PMS_INBOX')
				);
			}
			else
			{
				$this->_pm = $private->getInboxLink(JText::_('COM_KUNENA_PM_WRITE'));
			}
		}

		return $this->_pm;
	}

	/**
	 * Show email address if current user has permissions to see it.
	 *
	 * @param   mixed $profile profile
	 *
	 * @return bool Cloaked email address or empty string.
	 *
	 * @throws Exception
	 * @since  K5.1
	 */
	public function getEmail($profile)
	{
		$me     = KunenaUserHelper::getMyself();
		$config = KunenaConfig::getInstance();

		if ($me->isModerator() || $me->isAdmin())
		{
			return true;
		}

		if ($config->showemail && $profile->email)
		{
			if ($profile->hideEmail == 0)
			{
				return true;
			}

			if ($profile->hideEmail == 2 && $me->exists())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get email address if current user has permissions to see it.
	 *
	 * @return string  Cloaked email address or empty string.
	 *
	 * @throws Exception
	 * @since  K4.0
	 */
	public function getEmailLink()
	{
		if (!isset($this->_email))
		{
			$config = KunenaConfig::getInstance();
			$me     = KunenaUserHelper::getMyself();

			$this->_email = '';

			if ($this->email && (($config->showemail && (!$this->hideEmail || $me->isModerator())) || $me->isAdmin()))
			{
				$this->_email = JHtml::_('email.cloak', $this->email);
			}
		}

		return $this->_email;
	}

	/**
	 * Get website link from the user.
	 *
	 * @return string  Link to the website.
	 *
	 * @since  K4.0
	 */
	public function getWebsiteLink()
	{
		if (!isset($this->_website) && $this->websiteurl)
		{
			$this->_website = '';

			$url = $this->getWebsiteURL();

			$name = $this->getWebsiteName();

			$this->_website = '<a href="' . $this->escape($url) . '" target="_blank" rel="noopener noreferrer">' . $this->escape($name) . '</a>';
		}

		return (string) $this->_website;
	}

	/**
	 * Get website URL from the user.
	 *
	 * @return string  URL to the website.
	 *
	 * @since  K4.0
	 */
	public function getWebsiteURL()
	{
		$url = $this->websiteurl;

		if (!preg_match("~^(?:f|ht)tps?://~i", $this->websiteurl))
		{
			$url = 'http://' . $url;
		}

		return $url;
	}

	/**
	 * Get website name from the user.
	 *
	 * @return string  Name to the website or the URL if the name isn't set.
	 *
	 * @since  K4.0
	 */
	public function getWebsiteName()
	{
		$name = trim($this->websitename) ? $this->websitename : $this->websiteurl;

		return $name;
	}

	/**
	 * @param   string $var var
	 *
	 * @return string
	 * @since Kunena
	 */
	public function escape($var)
	{
		return htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Output gender.
	 *
	 * @param   bool $translate translate
	 *
	 * @return string  One of: male, female or unknown.
	 *
	 * @since  K4.0
	 */
	public function getGender($translate = true)
	{
		switch ($this->gender)
		{
			case 1 :
				$gender = 'male';
				break;
			case 2 :
				$gender = 'female';
				break;
			default :
				$gender = 'unknown';
		}

		return $translate ? JText::_('COM_KUNENA_MYPROFILE_GENDER_' . $gender) : $gender;
	}

	/**
	 * Render user signature.
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since  K4.0
	 */
	public function getSignature()
	{
		$config = KunenaConfig::getInstance();

		if (!$config->signature)
		{
			return false;
		}

		if (!isset($this->_signature))
		{
			$this->_signature = KunenaHtmlParser::parseBBCode($this->signature, $this, KunenaConfig::getInstance()->maxsig);
		}

		return $this->_signature;
	}

	/**
	 * Render user karma.
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since  K5.0
	 */
	public function getKarma()
	{
		$karma = '';

		if ($this->userid)
		{
			$config = KunenaConfig::getInstance();
			$me     = KunenaUserHelper::getMyself();

			$karma = $this->karma;

			if ($config->showkarma && $me->userid && $me->userid != $this->userid)
			{
				$topicicontype = KunenaFactory::getTemplate()->params->get('topicicontype');

				if ($topicicontype == 'B3')
				{
					$karmaMinusIcon = '<span class="glyphicon-karma glyphicon glyphicon-minus-sign text-danger" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></span>';
					$karmaPlusIcon  = '<span class="glyphicon-karma glyphicon glyphicon-plus-sign text-success" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></span>';
				}
				elseif ($topicicontype == 'fa')
				{
					$karmaMinusIcon = '<i class="fa fa-minus-circle" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></i>';
					$karmaPlusIcon  = '<i class="fa fa-plus-circle" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></i>';
				}
				elseif ($topicicontype == 'B2')
				{
					$karmaMinusIcon = '<span class="icon-karma icon icon-minus text-error" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></span>';
					$karmaPlusIcon  = '<span class="icon-karma icon icon-plus text-success" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></span>';
				}
				else
				{
					$karmaMinusIcon = '<span class="kicon-profile kicon-profile-minus" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></span>';
					$karmaPlusIcon  = '<span class="kicon-profile kicon-profile-plus" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></span>';
				}

				$karma .= ' ' . JHtml::_('kunenaforum.link', 'index.php?option=com_kunena&view=user&task=karmadown&userid=' . $this->userid . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1', $karmaMinusIcon);
				$karma .= ' ' . JHtml::_('kunenaforum.link', 'index.php?option=com_kunena&view=user&task=karmaup&userid=' . $this->userid . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1', $karmaPlusIcon);
			}
		}

		return $karma;
	}

	/**
	 * Render user sidebar.
	 *
	 * @param   KunenaLayout $layout layout
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since  K5.0
	 */
	public function getSideProfile($layout)
	{
		$config = KunenaFactory::getConfig();

		$view                  = clone $layout;
		$view->config          = $config;
		$view->userkarma_title = $view->userkarma_minus = $view->userkarma_plus = '';

		if ($view->config->showkarma && $this->userid)
		{
			$view->userkarma_title = JText::_('COM_KUNENA_KARMA') . ': ' . $this->karma;

			if ($view->me->userid && $view->me->userid != $this->userid)
			{
				$topicicontype = KunenaFactory::getTemplate()->params->get('topicicontype');

				if ($topicicontype == 'B3')
				{
					$karmaMinusIcon = '<span class="glyphicon-karma glyphicon glyphicon-minus-sign text-danger" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></span>';
					$karmaPlusIcon  = '<span class="glyphicon-karma glyphicon glyphicon-plus-sign text-success" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></span>';
				}
				elseif ($topicicontype == 'fa')
				{
					$karmaMinusIcon = '<i class="fa fa-minus-circle" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></i>';
					$karmaPlusIcon  = '<i class="fa fa-plus-circle" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></i>';
				}
				elseif ($topicicontype == 'B2')
				{
					$karmaMinusIcon = '<span class="icon-karma icon icon-minus text-error" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></span>';
					$karmaPlusIcon  = '<span class="icon-karma icon icon-plus text-success" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></span>';
				}
				else
				{
					$karmaMinusIcon = '<span class="kicon-profile kicon-profile-minus" title="' . JText::_('COM_KUNENA_KARMA_SMITE') . '"></span>';
					$karmaPlusIcon  = '<span class="kicon-profile kicon-profile-plus" title="' . JText::_('COM_KUNENA_KARMA_APPLAUD') . '"></span>';
				}

				$view->userkarma_minus = ' ' . JHtml::_('kunenaforum.link', 'index.php?option=com_kunena&view=user&task=karmadown&userid=' . $this->userid . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1', $karmaMinusIcon);
				$view->userkarma_plus  = ' ' . JHtml::_('kunenaforum.link', 'index.php?option=com_kunena&view=user&task=karmaup&userid=' . $this->userid . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1', $karmaPlusIcon);
			}
		}

		$view->userkarma = "{$view->userkarma_title} {$view->userkarma_minus} {$view->userkarma_plus}";

		if ($view->config->showuserstats)
		{
			$view->userrankimage = $this->getRank($layout->category->id, 'image');
			$view->userranktitle = $this->getRank($layout->category->id, 'title');
			$view->userposts     = $this->posts;
			$view->userthankyou  = $this->thankyou;
			$activityIntegration = KunenaFactory::getActivityIntegration();
			$view->userpoints    = $activityIntegration->getUserPoints($this->userid);
			$view->usermedals    = $activityIntegration->getUserMedals($this->userid);
		}
		else
		{
			$view->userrankimage = null;
			$view->userranktitle = null;
			$view->userposts     = null;
			$view->userthankyou  = null;
			$view->userpoints    = null;
			$view->usermedals    = null;
		}

		$view->personalText = $this->getPersonalText();

		$params = new \Joomla\Registry\Registry;
		$params->set('ksource', 'kunena');
		$params->set('kunena_view', 'topic');
		$params->set('kunena_layout', $layout->getLayout());

		\Joomla\CMS\Plugin\PluginHelper::importPlugin('kunena');

		\JFactory::getApplication()->triggerEvent('onKunenaSidebar', array($this->userid));

		return KunenaFactory::getProfile()->showProfile($view, $params);
	}

	/**
	 * @param   int       $catid   Category Id for the rank (user can have different rank in different categories).
	 * @param   string    $type    Possible values: 'title' | 'image' | false (for object).
	 * @param   bool|null $special True if special only, false if post count, otherwise combined.
	 *
	 * @return stdClass|string|null
	 * @throws Exception
	 * @since Kunena
	 */
	public function getRank($catid = 0, $type = null, $special = null)
	{
		$config = KunenaConfig::getInstance();

		if (!$config->showranking)
		{
			return;
		}

		// Guests do not have post rank, they only have special rank.
		if ($special === false && !$this->userid)
		{
			return;
		}

		// First run? Initialize ranks.
		if (self::$_ranks === null)
		{
			$this->_db->setQuery("SELECT * FROM #__kunena_ranks");

			try
			{
				self::$_ranks = $this->_db->loadObjectList('rank_id');
			}
			catch (JDatabaseExceptionExecuting $e)
			{
				KunenaError::displayDatabaseError($e);
			}
		}

		$userType = $special !== false ? $this->getType($catid, true) : 'count';

		if (isset(self::$_ranks[$this->rank]) && !in_array($userType, array('guest', 'blocked', 'banned', 'count')))
		{
			// Use rank specified to the user.
			$rank = self::$_ranks[$this->rank];
		}
		else
		{
			// Generate user rank.
			$rank               = new stdClass;
			$rank->rank_id      = 0;
			$rank->rank_title   = JText::_('COM_KUNENA_RANK_USER');
			$rank->rank_min     = 0;
			$rank->rank_special = 0;
			$rank->rank_image   = 'rank0.gif';

			switch ($userType)
			{
				case 'guest' :
					$rank->rank_title   = JText::_('COM_KUNENA_RANK_VISITOR');
					$rank->rank_special = 1;

					foreach (self::$_ranks as $cur)
					{
						if ($cur->rank_special == 1 && strstr($cur->rank_image, 'guest'))
						{
							$rank = $cur;
							break;
						}
					}
					break;

				case 'blocked' :
					$rank->rank_title   = JText::_('COM_KUNENA_RANK_BLOCKED');
					$rank->rank_special = 1;
					$rank->rank_image   = 'rankdisabled.gif';

					foreach (self::$_ranks as $cur)
					{
						if ($cur->rank_special == 1 && strstr($cur->rank_image, 'disabled'))
						{
							$rank = $cur;
							break;
						}
					}
					break;

				case 'banned' :
					$rank->rank_title   = JText::_('COM_KUNENA_RANK_BANNED');
					$rank->rank_special = 1;
					$rank->rank_image   = 'rankbanned.gif';

					foreach (self::$_ranks as $cur)
					{
						if ($cur->rank_special == 1 && strstr($cur->rank_image, 'banned'))
						{
							$rank = $cur;
							break;
						}
					}
					break;

				case 'admin' :
				case 'localadmin' :
					$rank->rank_title   = JText::_('COM_KUNENA_RANK_ADMINISTRATOR');
					$rank->rank_special = 1;
					$rank->rank_image   = 'rankadmin.gif';

					foreach (self::$_ranks as $cur)
					{
						if ($cur->rank_special == 1 && strstr($cur->rank_image, 'admin'))
						{
							$rank = $cur;
							break;
						}
					}
					break;

				case 'globalmod' :
				case 'moderator' :
					$rank->rank_title   = JText::_('COM_KUNENA_RANK_MODERATOR');
					$rank->rank_special = 1;
					$rank->rank_image   = 'rankmod.gif';

					foreach (self::$_ranks as $cur)
					{
						if ($cur->rank_special == 1
							&& (strstr($cur->rank_image, 'rankmod') || strstr($cur->rank_image, 'moderator'))
						)
						{
							$rank = $cur;
							break;
						}
					}
					break;

				case 'user' :
				case 'count' :
					foreach (self::$_ranks as $cur)
					{
						if ($cur->rank_special == 0 && $cur->rank_min <= $this->posts && $cur->rank_min >= $rank->rank_min)
						{
							$rank = $cur;
						}
					}
					break;
			}
		}

		if ($special === true && !$rank->rank_special)
		{
			return;
		}

		if ($type == 'title')
		{
			return $rank->rank_title;
		}

		if (!$config->rankimages)
		{
			$rank->rank_image = null;
		}

		/**
		 *  Rankimages 0 = Text Rank
		 *             1 = Rank Image
		 *             2 = Usergroup
		 *             3 = Both Rank image and Usergroup
		 */
		if ($config->rankimages == 0)
		{
			return false;
		}
		elseif ($config->rankimages == 1)
		{
			$url      = KunenaTemplate::getInstance()->getRankPath($rank->rank_image, true);
			$location = JPATH_SITE . '/media/kunena/ranks/' . $rank->rank_image;
			$data     = getimagesize($location);
			$width    = $data[0];
			$height   = $data[1];

			return '<img src="' . $url . '" height="' . $height . '" width="' . $width . '" alt="' . $rank->rank_title . '" />';
		}
		elseif ($config->rankimages == 2)
		{
			return '<span class="ranksusergroups">' . self::getUserGroup($this->userid) . '</span>';
		}
		elseif ($config->rankimages == 3)
		{
			$url      = KunenaTemplate::getInstance()->getRankPath($rank->rank_image, true);
			$location = JPATH_SITE . '/media/kunena/ranks/' . $rank->rank_image;
			$data     = getimagesize($location);
			$width    = $data[0];
			$height   = $data[1];

			return '<img src="' . $url . '" height="' . $height . '" width="' . $width . '" alt="' . $rank->rank_title . '" /><br>
				<span class="ranksusergroups">' . self::getUserGroup($this->userid) . '</span>';
		}
		elseif ($config->rankimages == 4)
		{
			return self::rankCss($rank, $catid);
		}

		return $rank;
	}

	/**
	 * Get users type as a string inside the specified category.
	 *
	 * @param $rank
	 *
	 * @param $catid
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function rankCss($rank, $catid)
	{
		$ktemplate     = KunenaFactory::getTemplate();
		$topicicontype = $ktemplate->params->get('topicicontype');

		if ($topicicontype == 'fa')
		{
			if ($rank->rank_title == 'New Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i>
			</li>';
			}
			elseif ($rank->rank_title == 'Junior Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i>
			</li>';
			}
			elseif ($rank->rank_title == 'Senior Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i>
			</li>';
			}
			elseif ($rank->rank_title == 'Premium Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle" style="color:#e8f7ff;"></i>
			</li>';
			}
			elseif ($rank->rank_title == 'Elite Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i>
			</li>';
			}
			elseif ($rank->rank_title == 'Platinum Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i>
			</li>';
			}

			return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i><i class="fas fa-circle"></i>
			</li>';
		}
		elseif ($topicicontype == 'B2')
		{
			if ($rank->rank_title == 'New Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Junior Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Senior Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Premium Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Elite Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Platinum Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span>
			</li>';
			}

			return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span><span class="icon icon-one-fine-dot"></span>
			</li>';
		}
		elseif ($topicicontype == 'B3')
		{
			if ($rank->rank_title == 'New Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Junior Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Senior Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Premium Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot" style="color:#e8f7ff;"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Elite Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span>
			</li>';
			}
			elseif ($rank->rank_title == 'Platinum Member')
			{
				return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span>
			</li>';
			}

			return '<li class="kwho-' . $this->getType($catid, true) . '" alt="' . $rank->rank_title . '">
				<span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span><span class="glyphicon glyphicon-one-fine-dot"></span>
			</li>';
		}
	}

	/**
	 * Get users type as a string inside the specified category.
	 *
	 * @param   int  $catid Category id or 0 for global.
	 * @param   bool $code  True if we want to return the code, otherwise return translation key.
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getType($catid = 0, $code = false)
	{
		static $types = array(
			'admin'      => 'COM_KUNENA_VIEW_ADMIN',
			'localadmin' => 'COM_KUNENA_VIEW_ADMIN',
			'globalmod'  => 'COM_KUNENA_VIEW_GLOBAL_MODERATOR',
			'moderator'  => 'COM_KUNENA_VIEW_MODERATOR',
			'user'       => 'COM_KUNENA_VIEW_USER',
			'guest'      => 'COM_KUNENA_VIEW_VISITOR',
			'banned'     => 'COM_KUNENA_VIEW_BANNED',
			'blocked'    => 'COM_KUNENA_VIEW_BLOCKED',
		);

		$adminCategories     = KunenaAccess::getInstance()->getAdminStatus($this);
		$moderatedCategories = KunenaAccess::getInstance()->getModeratorStatus($this);

		if ($this->userid == 0)
		{
			$type = 'guest';
		}
		elseif ($this->isBlocked())
		{
			$type = 'blocked';
		}
		elseif ($this->isBanned())
		{
			$type = 'banned';
		}
		elseif (!empty($adminCategories[0]))
		{
			$type = 'admin';
		}
		elseif (!empty($adminCategories[$catid]))
		{
			$type = 'localadmin';
		}
		elseif (!empty($moderatedCategories[0]))
		{
			$type = 'globalmod';
		}
		elseif (!empty($moderatedCategories[$catid]))
		{
			$type = 'moderator';
		}
		elseif (!$catid && !empty($moderatedCategories))
		{
			$type = 'moderator';
		}
		else
		{
			$type = 'user';
		}

		if ($code === 'class')
		{
			$userClasses = KunenaFactory::getTemplate()->getUserClasses();

			return isset($userClasses[$type]) ? $userClasses[$type] : $userClasses[0] . $type;
		}

		return $code ? $type : $types[$type];
	}

	/**
	 * @return boolean
	 * @since Kunena
	 */
	public function isBlocked()
	{
		if ($this->blocked)
		{
			return true;
		}

		return false;
	}

	/**
	 * @return boolean
	 * @since Kunena
	 */
	public function isBanned()
	{
		if (!$this->banned)
		{
			return false;
		}

		if ($this->blocked || $this->banned == $this->_db->getNullDate())
		{
			return true;
		}
	}

	/**
	 * @return mixed
	 * @since Kunena
	 */
	public function bannedDate()
	{
		$ban = new \Joomla\CMS\Date\Date($this->banned);
		$now = new \Joomla\CMS\Date\Date;

		return $ban->toUnix() > $now->toUnix();
	}

	/**
	 * @param   integer $userid userid
	 *
	 * @return mixed|string
	 *
	 * @since version
	 */
	public function GetUserGroup($userid)
	{
		jimport('joomla.access.access');
		$groups = \Joomla\CMS\Access\Access::getGroupsByUser($userid, false);

		$groupid_list = implode(',', $groups);

		foreach ($groups as $groupId => $value)
		{
			$db    = \Joomla\CMS\Factory::getDbo();
			$query = $db->getQuery(true)
				->select('title')
				->from('#__usergroups')
				->where('id = ' . (int) $groupid_list);

			$db->setQuery($query);
			$groupNames = $db->loadResult();
			$groupNames .= '<br/>';
		}

		return $groupNames;
	}

	/**
	 * Render personal text.
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since  K4.0
	 */
	public function getPersonalText()
	{
		$config = KunenaConfig::getInstance();

		if (!$config->personal)
		{
			return false;
		}

		if (!isset($this->_personalText))
		{
			$this->_personalText = KunenaHtmlParser::parseText($this->personalText);
		}

		return $this->_personalText;
	}

	/**
	 * @param   string $name name
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function profileIcon($name)
	{
		switch ($name)
		{
			case 'gender' :
				switch ($this->gender)
				{
					case 1 :
						$gender = 'male';
						break;
					case 2 :
						$gender = 'female';
						break;
					default :
						$gender = 'unknown';
				}

				$title = JText::_('COM_KUNENA_MYPROFILE_GENDER') . ': ' . JText::_('COM_KUNENA_MYPROFILE_GENDER_' . $gender);

				return '<span class="kicon-profile kicon-profile-gender-' . $gender . '" data-toggle="tooltip" data-placement="right" title="' . $title . '"></span>';
				break;
			case 'birthdate' :
				if ($this->birthdate)
				{
					$date = new KunenaDate($this->birthdate);

					if ($date->format('%Y') < 1902)
					{
						break;
					}

					return '<span class="kicon-profile kicon-profile-birthdate" data-toggle="tooltip" data-placement="right" title="' . JText::_('COM_KUNENA_MYPROFILE_BIRTHDATE') . ': ' . $this->birthdate->toKunena('date', 'GMT') . '"></span>';
				}
				break;
			case 'location' :
				if ($this->location)
				{
					return '<span data-toggle="tooltip" data-placement="right" title="' . $this->escape($this->location) . '">' . KunenaIcons::location() . '</span>';
				}
				break;
			case 'website' :
				$url = $this->websiteurl;

				if (!preg_match("~^(?:f|ht)tps?://~i", $this->websiteurl))
				{
					$url = 'http://' . $this->websiteurl;
				}

				if (!$this->websitename)
				{
					$websitename = $this->websiteurl;
				}
				else
				{
					$websitename = $this->websitename;
				}

				if ($this->websiteurl)
				{
					return '<a href="' . $this->escape($url) . '" target="_blank" rel="noopener noreferrer"><span data-toggle="tooltip" data-placement="right" title="' . $websitename . '">' . KunenaIcons::globe() . '</span></a>';
				}
				break;
			case 'private' :
				$pms = KunenaFactory::getPrivateMessaging();

				return '<span data-toggle="tooltip" data-placement="right" title="' . JText::_('COM_KUNENA_VIEW_PMS') . '" >' . $pms->showIcon($this->userid) . '</span>';
				break;
			case 'email' :
				return '<span data-toggle="tooltip" data-placement="right" title="' . $this->email . '">' . KunenaIcons::email() . '</span>';
				break;
			case 'profile' :
				if (!$this->userid)
				{
					return;
				}

				return $this->getLink('<span class="profile" title="' . JText::_('COM_KUNENA_VIEW_PROFILE') . '"></span>');
				break;
		}
	}

	/**
	 * @param   null|string $name       name
	 * @param   null|string $title      title
	 * @param   string      $rel        rel
	 * @param   string      $task       task
	 * @param   string      $class      class
	 * @param   int         $catid      catid
	 * @param   int         $avatarLink avatarlink
	 *
	 * @return string
	 * @throws Exception
	 * @since Kunena
	 */
	public function getLink($name = null, $title = null, $rel = 'nofollow', $task = '', $class = null, $catid = 0, $avatarLink = 0)
	{
		if (!$name)
		{
			$name = $this->getName();
		}

		$key = "{$name}.{$title}.{$rel}.{$catid}";

		if (empty($this->_link[$key]))
		{
			if (!$title)
			{
				$title = JText::sprintf('COM_KUNENA_VIEW_USER_LINK_TITLE', $this->getName());
			}

			$class = ($class !== null) ? $class : $this->getType($catid, 'class');

			if (!empty($class))
			{
				if ($class == 'btn')
				{
				}
				elseif ($class == 'btn btn-default')
				{
				}
				elseif ($class == 'btn pull-right')
				{
				}
				elseif ($class == 'btn btn-default pull-right')
				{
				}
				else
				{
					$class = $this->getType($catid, 'class');
				}
			}

			if (KunenaTemplate::getInstance()->tooltips())
			{
				$class = $class . ' ' . KunenaTemplate::getInstance()->tooltips();
			}

			if ($this->userid == \Joomla\CMS\Factory::getUser()->id && $avatarLink)
			{
				$link = KunenaFactory::getProfile()->getEditProfileURL($this->userid);
			}
			else
			{
				$link = $this->getURL(true, $task);
			}

			if (!empty($rel))
			{
				$rels = 'rel="' . $rel . '"';
			}
			else
			{
				$rels = '';
			}

			if ($rels == 'rel="canonical"')
			{
				$config          = \Joomla\CMS\Factory::getApplication('site');
				$componentParams = $config->getParams('com_config');
				$robots          = $componentParams->get('robots');

				if ($robots == 'noindex, follow' || $robots == 'noindex, nofollow')
				{
					$rels = 'rel="nofollow"';
				}
				else
				{
					$rels = 'rel="canonical"';
				}
			}

			if (!empty($link))
			{
				$this->_link[$key] = "<a class=\"{$class}\" href=\"{$link}\" title=\"{$title}\" {$rels}>{$name}</a>";
			}
			else
			{
				$this->_link[$key] = "<span class=\"{$class}\">{$name}</span>";
			}
		}

		return $this->_link[$key];
	}

	/**
	 * Legacy method to prepare social buttons for the template
	 *
	 * @param   string $name name
	 * @param   bool   $gray gray
	 *
	 * @throws Exception
	 * @deprecated 5.1.0
	 * @since      K2.0
	 * @return void
	 */
	public function socialButton($name, $gray = false)
	{
		$this->socialButtonsTemplate($name, $gray = false);
	}

	/**
	 * Prepare social buttons for the template
	 *
	 * @param   string $name name
	 * @param   bool   $gray gray
	 *
	 * @return string
	 * @throws Exception
	 * @since K5.0
	 */
	public function socialButtonsTemplate($name, $gray = false)
	{
		$social = $this->socialButtons();

		if (!isset($social [$name]))
		{
			return false;
		}

		$title = $social [$name] ['title'];
		$value = $this->escape($this->$name);
		$url   = strtr($social [$name] ['url'], array('##VALUE##' => $value));

		// TODO : move this part in a template

		if ($social [$name] ['nourl'] == '0')
		{
			if (!empty($this->$name))
			{
				return '<a href="' . $this->escape($url) . '" ' . KunenaTemplate::getInstance()->tooltips(true) . ' target="_blank" title="' . $title . ': ' . $value . '"><span class="kicon-profile kicon-profile-' . $name . '"></span></a>';
			}
		}
		else
		{
			if (!empty($this->$name))
			{
				return '<span class="kicon-profile kicon-profile-' . $name . ' ' . KunenaTemplate::getInstance()->tooltips() . '" title="' . $title . ': ' . $value . '"></span>';
			}
		}

		if ($gray)
		{
			return '<span class="kicon-profile kicon-profile-' . $name . '-off"></span>';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get list of social buttons
	 *
	 * @return array|string
	 * @since Kunena
	 */
	public function socialButtons()
	{
		$social = array('twitter'    => array('url' => 'https://twitter.com/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_TWITTER'), 'nourl' => '0'),
		                'facebook'   => array('url' => 'https://www.facebook.com/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_FACEBOOK'), 'nourl' => '0'),
		                'myspace'    => array('url' => 'https://www.myspace.com/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_MYSPACE'), 'nourl' => '0'),
		                'linkedin'   => array('url' => 'https://www.linkedin.com/in/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_LINKEDIN'), 'nourl' => '0'),
		                'delicious'  => array('url' => 'https://del.icio.us/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_DELICIOUS'), 'nourl' => '0'),
		                'friendfeed' => array('url' => 'http://friendfeed.com/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_FRIENDFEED'), 'nourl' => '0'),
		                'digg'       => array('url' => 'http://www.digg.com/users/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_DIGG'), 'nourl' => '0'),
		                'skype'      => array('url' => 'skype:##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_SKYPE'), 'nourl' => '0'),
		                'yim'        => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_YIM'), 'nourl' => '1'),
		                'google'     => array('url' => 'https://plus.google.com/+##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_GOOGLE'), 'nourl' => '0'),
		                'microsoft'  => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_MICROSOFT'), 'nourl' => '1'),
		                'icq'        => array('url' => 'https://icq.com/people/cmd.php?uin=##VALUE##&action=message', 'title' => JText::_('COM_KUNENA_MYPROFILE_ICQ'), 'nourl' => '0'),
		                'blogspot'   => array('url' => 'https://##VALUE##.blogspot.com/', 'title' => JText::_('COM_KUNENA_MYPROFILE_BLOGSPOT'), 'nourl' => '0'),
		                'flickr'     => array('url' => 'https://www.flickr.com/photos/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_FLICKR'), 'nourl' => '0'),
		                'bebo'       => array('url' => 'https://www.bebo.com/Profile.jsp?MemberId=##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_BEBO'), 'nourl' => '0'),
		                'instagram'  => array('url' => 'https://www.instagram.com/##VALUE##/', 'title' => JText::_('COM_KUNENA_MYPROFILE_INSTAGRAM'), 'nourl' => '0'),
		                'qq'         => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_QQ'), 'nourl' => '1'),
		                'qzone'      => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_QZONE'), 'nourl' => '1'),
		                'weibo'      => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_WEIBO'), 'nourl' => '1'),
		                'wechat'     => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_WECHAT'), 'nourl' => '1'),
		                'vk'         => array('url' => 'https://vk.com/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_VK'), 'nourl' => '0'),
		                'telegram'   => array('url' => 'https://t.me/##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_TELEGRAM'), 'nourl' => '0'),
		                'apple'      => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_APPLE'), 'nourl' => '1'),
		                'whatsapp'   => array('url' => '##VALUE##', 'title' => JText::_('COM_KUNENA_MYPROFILE_WHATSAPP'), 'nourl' => '1'),
		);

		return $social;
	}

	/**
	 * @param   string $name name
	 *
	 * @return mixed
	 * @since Kunena
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'id':

				return $this->userid;
		}

		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE
		);

		return;
	}

	/**
	 * Check if captcha is allowed for guests users or registered users
	 *
	 * @return boolean
	 * @throws Exception
	 * @since Kunena
	 */
	public function canDoCaptcha()
	{
		$config = KunenaFactory::getConfig();

		if (!$this->exists() && $config->captcha == 1)
		{
			return true;
		}

		if ($this->exists() && !$this->isModerator() && $config->captcha >= 0)
		{
			if ($config->captcha_post_limit > 0 && $this->posts < $config->captcha_post_limit)
			{
				return true;
			}
		}

		if ($config->captcha == '-1')
		{
			return false;
		}
	}
}
