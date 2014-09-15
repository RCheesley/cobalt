<?php
/**
 * @package    Cobalt.CRM
 *
 * @copyright  Copyright (C) 2012 Cobalt. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Cobalt;

defined('_CEXEC') or die;

use JFactory;
use JPluginHelper;
use JDocument;

use JUri as Uri;
use Joomla\String\String;
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;
use Joomla\Language\Language;
use Joomla\Language\Text;
use Joomla\Application\AbstractWebApplication;
use Cobalt\Model\User as UserModel;
use Cobalt\User;
use Cobalt\Helper\RouteHelper;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Cobalt Application class
 *
 * Provide many supporting API functions
 *
 * @package    	Cobalt.CRM
 * @subpackage  Application
 * @since       1.5
 */
final class Application extends AbstractWebApplication
{
	/**
	 * DI Container
	 *
	 * @var    Container
	 * @since  1.0
	 */
	private $container;

    /**
     * The Dispatcher object.
     *
     * @var    Dispatcher
     * @since  1.0
     */
    protected $dispatcher;

	/**
	 * A session object.
	 *
	 * @var    Session
	 * @since  1.0
	 * @note   This has been created to avoid a conflict with the $session member var from the parent class.
	 */
	private $cSession = null;

    /**
     * Currently active template
     * @var object
     */
    private $template = null;

    /**
     * Option to filter by language
     */
    private $_language_filter = false;

    /**
     * Option to detect language by the browser
     */
    private $_detect_browser = false;

    /**
     * The Application router
     */
    public $router = null;

    /**
     * JDocument
     *
     * @var  JDocument
     */
    public $document = null;

    /**
     * The Unique Application Identifier
     */
    public $_name = 'Cobalt';

    /**
    * The Unique Client ID
    */
    protected $_clientId = null;

    /**
     * The application message queue.
     *
     * @var    array
     * @since  1.0
     */
    protected $messageQueue = array();

    protected $users = array();
    protected $user = null;

    /**
     * The Language object
     *
     * @var    Language
     * @since  1.0
     */
    private $language;

	/**
	 * Application constructor
	 *
	 * @since   1.0
	 */
	public function __construct()
    {
        parent::__construct();

	    $container = Container::getInstance();

	    $container
	        ->registerServiceProvider(new Provider\ApplicationServiceProvider($this))
	        ->registerServiceProvider(new Provider\ConfigServiceProvider)
	        ->registerServiceProvider(new Provider\DatabaseServiceProvider)
            ->registerServiceProvider(new Provider\SessionServiceProvider);

        // Setup the application pieces.
	    $this->setContainer($container);
        $this->loadConfiguration();
        $this->loadDispatcher();
        $this->loadDocument();

        // Load the library language file
        $this->getLanguage()->load('lib_joomla', JPATH_BASE);

	    // TODO - NO MORE JFACTORY
	    JFactory::$application = $this;
    }

    /**
     * Initialize the configuration object.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     * @throws \RuntimeException
     */
    private function loadConfiguration()
    {
        $config = $this->getContainer()->get('config');

        if ($config === null)
        {
            throw new \RuntimeException(sprintf('Unable to parse the configuration file %s.', $file));
        }

        $this->config->merge($config);

        return $this;
    }

    /**
     * Allows the application to load a custom or default dispatcher.
     *
     * The logic and options for creating this object are adequately generic for default cases
     * but for many applications it will make sense to override this method and create event
     * dispatchers, if required, based on more specific needs.
     *
     * @param Dispatcher $dispatcher An optional dispatcher object. If omitted, the factory dispatcher is created.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     */
    public function loadDispatcher(Dispatcher $dispatcher = null)
    {
        $this->dispatcher = ($dispatcher === null) ? new Dispatcher : $dispatcher;

        return $this;
    }

	/**
	 * Get the DI container.
	 *
	 * @return  Container
	 *
	 * @since   1.0
	 *
	 * @throws  \UnexpectedValueException May be thrown if the container has not been set.
	 */
	public function getContainer()
	{
		if ($this->container)
		{
			return $this->container;
		}

		throw new \UnexpectedValueException('Container not set in ' . __CLASS__);
	}

	/**
	 * Set the DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}

    /**
     * Get a language object.
     *
     * @return Language
     *
     * @since   1.0
     */
    public function getLanguage()
    {
        if (is_null($this->language)) {
            $this->language = Language::getInstance(
                $this->get('language'),
                $this->get('debug_lang')
            );

            // Configure Text to use language instance
            Text::setLanguage($this->language);
        }

        return $this->language;
    }

    /**
     * Provides a secure hash based on a seed
     *
     * @param string $seed Seed string.
     *
     * @return string A secure hash
     *
     * @since   11.1
     */
    public static function getHash($seed)
    {
	    // TODO - Temporarily refactored back to JFactory, needs to pull from $this->getContainer instead
        return md5(JFactory::getConfig()->get('secret') . $seed);
    }

    /**
     * Get a session object.
     *
     * @return  Session
     *
     * @since   1.0
     */
    public function getSession()
    {
	    if (is_null($this->cSession))
	    {
    		$this->cSession = $this->getContainer()->fetch('session');
	    }
        
        return $this->cSession;
    }

    /**
     * Clear session + database session row
     *
     * @return boolean
     *
     * @since   1.0
     */
    public function clearSession()
    {
        $this->getSession()->clear();
    }


    /**
     * Gets the value of a user state variable.
     *
     * @param string $key     The key of the user state variable.
     * @param string $request The name of the variable passed in a request.
     * @param string $default The default value for the variable if not found. Optional.
     * @param string $type    Filter for the variable, for valid values see {@link JFilterInput::clean()}. Optional.
     *
     * @return The request user state.
     *
     * @since   11.1
     */
    public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
    {
        $cur_state = $this->getUserState($key, $default);
        $new_state = $this->input->get($request, null, 'default', $type);

        // Save the new value only if it was set in this request.
        if ($new_state !== null) {
            $this->setUserState($key, $new_state);
        } else {
            $new_state = $cur_state;
        }

        return $new_state;
    }

    /**
     * Gets a user state.
     *
     * @param string $key     The path of the state.
     * @param mixed  $default Optional default value, returned if the internal value is null.
     *
     * @return mixed The user state or null.
     *
     * @since   1.0
     */
    public function getUserState($key, $default = null)
    {
        /* @type Registry $registry */
        $registry = $this->getSession()->get('registry');

        if (!is_null($registry)) {
            return $registry->get($key, $default);
        }

        return $default;
    }

    /**
     * Sets the value of a user state variable.
     *
     * @param string $key   The path of the state.
     * @param string $value The value of the variable.
     *
     * @return mixed The previous state, if one existed.
     *
     * @since   11.1
     */
    public function setUserState($key, $value)
    {
        $registry = $this->getSession()->get('registry');

        if (!is_null($registry)) {
            return $registry->set($key, $value);
        }

        return null;
    }

    public function doExecute()
    {
        // Register the template to the config
        $template = $this->getTemplate(true);
        $this->set('theme', $template->template);
        $this->set('themeFile', $this->input->get('tmpl', 'index') . '.php');

        // Set metadata
        $this->document->setTitle('Cobalt');

        ob_start();
        require_once __DIR__.'/cobalt.php';
        $contents = ob_get_clean();

        // Trigger the onAfterDispatch event.
        JPluginHelper::importPlugin('system');
        $this->triggerEvent('onAfterDispatch');

        if ($this->input->get('format', 'html') === 'raw') {
            $this->setBody($contents);
        } else {
            $this->document->setBuffer($contents, 'cobalt');
            $this->setBody($this->document->render(false, (array) $template));
        }
    }

    public function loadDocument()
    {
        if (empty($this->document)) {
            $this->document = JDocument::getInstance();
            JFactory::$document = $this->document;
        }
    }

    /**
     * @return JDocument
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Login authentication function
     *
     * @param	array	Array('username' => string, 'password' => string)
     * @param	array	Array('remember' => boolean)
     *
     * @see JApplication::login
     */
    public function login($credentials, $options = array())
    {
        // Set the application login entry point
        if (!array_key_exists('entry_url', $options))
        {
            $options['entry_url'] = \RouteHelper::_('index.php?view=login');
        }

        // Set the access control action to check.
        $options['action'] = 'core.login.site';

        $user = $this->getUser();

        if ($user->get('id'))
        {
            $this->setUser($user);
            // user is already logged in
            $this->redirect(\RouteHelper::_('index.php?view=dashboard'));
        }

        $userModel = new UserModel;

        if ($userId = $userModel->authenticate($credentials, $options))
        {
            $user->load((int)$userId);
            $this->setUser($user);

            $this->redirect(\RouteHelper::_('index.php?view=dashboard'));
        }
        else
        {
            $this->redirect(\RouteHelper::_('index.php?view=login'));
        }
    }

    public function logout()
    {
        $userModel = new UserModel;
        // Perform the log out.
        $error = $userModel->logout();

        // Check if the log out succeeded.
        if (!($error instanceof \Exception)) {
            // Get the return url from the request and validate that it is internal.
            // Lw== is / encoded
            $return = base64_decode($this->input->get('return', 'Lw=='));
            if (!Uri::isInternal($return)) {
                $return = '';
            }

            // Redirect the user.
            $this->redirect(RouteHelper::_($return, false));
        } else {
            $this->redirect(RouteHelper::_('index.php', false));
        }

    }

    /**
     * Get the application parameters
     *
     * @param	string	The component option
     * @return object The parameters object
     * @since	1.5
     */
    public function getParams($option = null)
    {
        static $params = array();

        $hash = '__default';
        if (!empty($option)) {
            $hash = $option;
        }
        if (!isset($params[$hash])) {
            // Get component parameters
            if (!$option) {
                $option = $this->input->get('option');
            }
            // Get new instance of component global parameters
            $params[$hash] = new Registry;

            // Get language
            $lang_code = JFactory::getLanguage()->getTag();
            // $languages = JLanguageHelper::getLanguages('lang_code');
            $languages = array('en-GB');

            $title = $this->get('sitename');
            if (isset($languages[$lang_code]) && $languages[$lang_code]->metadesc) {
                $description = $languages[$lang_code]->metadesc;
            } else {
                $description = $this->get('MetaDesc');
            }
            $rights = $this->get('MetaRights');
            $robots = $this->get('robots');

            $title = '';
            $params[$hash]->def('page_title', $title);
            $params[$hash]->def('page_description', $description);
            $params[$hash]->def('page_rights', $rights);
            $params[$hash]->def('robots', $robots);
        }

        return $params[$hash];
    }

    /**
     * Get the template
     *
     * @return string The template name
     * @since 1.0
     */
    public function getTemplate($params = false)
    {
        if (is_object($this->template)) {
            if ($params) {
                return $this->template;
            }

            return $this->template->template;
        }

        // Fallback template
        $template = new \stdClass;

        $template->template = 'bootstrap'; //'default';
        if (!file_exists(JPATH_THEMES . '/default/index.php')) {
            $template->template = '';
        }

        $template->file = $this->input->get('tmpl', 'index').'.php';
        $template->directory = 'themes';


        $this->template = $template;
        if ($params) {
            return $template;
        }

        return $template->template;
    }

    /**
     * Overrides the default template that would be used
     *
     * @param string	The template name
     * @param mixed		The template style parameters
     */
    public function setTemplate($template, $styleParams = null)
    {
        if (is_dir(JPATH_THEMES . '/' . $template)) {
            $this->template = new \stdClass;
            $this->template->template = $template;
            if ($styleParams instanceof Registry) {
                $this->template->params = $styleParams;
            } else {
                $this->template->params = new Registry($styleParams);
            }
        }
    }

    /**
     * Allows the application to load a custom or default router.
     *
     * @return Router
     *
     * @since   1.0
     */
    public function getRouter()
    {
        if (is_null($this->router)) {
            $this->router = new Router($this->input, $this);

            $maps = json_decode(file_get_contents(JPATH_BASE . '/src/routes.json'));

            if (!$maps) {
                throw new \RuntimeException('Invalid router file.');
            }

            $this->router->addMaps($maps, true);
            $this->router->setDefaultController('Cobalt\\Controller\\DefaultController');
        }

        return $this->router;
    }

    /**
     * Return the current state of the language filter.
     *
     * @return boolean
     * @since	1.6
     */
    public function getLanguageFilter()
    {
        return $this->_language_filter;
    }

    /**
     * Set the current state of the language filter.
     *
     * @return boolean The old state
     * @since	1.6
     */
    public function setLanguageFilter($state=false)
    {
        $old = $this->_language_filter;
        $this->_language_filter = $state;

        return $old;
    }
    /**
     * Return the current state of the detect browser option.
     *
     * @return boolean
     * @since	1.6
     */
    public function getDetectBrowser()
    {
        return $this->_detect_browser;
    }

    /**
     * Set the current state of the detect browser option.
     *
     * @return boolean The old state
     * @since	1.6
     */
    public function setDetectBrowser($state=false)
    {
        $old = $this->_detect_browser;
        $this->_detect_browser = $state;

        return $old;
    }

    /**
     * Redirect to another URL.
     *
     * Optionally enqueues a message in the system message queue (which will be displayed
     * the next time a page is loaded) using the enqueueMessage method. If the headers have
     * not been sent the redirect will be accomplished using a "301 Moved Permanently"
     * code in the header pointing to the new location. If the headers have already been
     * sent this will be accomplished using a JavaScript statement.
     *
     * @param	string	The URL to redirect to. Can only be http/https URL
     * @param	string	An optional message to display on redirect.
     * @param	string  An optional message type.
     * @param	boolean	True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
     * @param	boolean	True if the enqueued messages are passed to the redirection, false else.
     * @return none; calls exit().
     * @since	1.5
     * @see		JApplication::enqueueMessage()
     */
    public function redirect($url, $msg='', $msgType='message', $moved = false, $persistMsg = true)
    {
        if (!$persistMsg) {
            $this->_messageQueue = array();
        }
        parent::redirect($url, $msg, $msgType, $moved);
    }

    public function getClientId()
    {
        return $this->_clientId;
    }

    /**
     * Registers a handler to a particular event group.
     *
     * @param string   $event   The event name.
     * @param callable $handler The handler, a function or an instance of a event object.
     *
     * @return Application The application to allow chaining.
     *
     * @since   12.1
     */
    public function registerEvent($event, $handler)
    {
        if ($this->dispatcher instanceof Dispatcher) {
            $this->dispatcher->triggerEvent($event, $handler);
        }

        return $this;
    }

    /**
     * Calls all handlers associated with an event group.
     *
     * @param string $event The event name.
     * @param array  $args  An array of arguments (optional).
     *
     * @return array An array of results from each function call, or null if no dispatcher is defined.
     *
     * @since   12.1
     */
    public function triggerEvent($event, array $args = null)
    {
        if ($this->dispatcher instanceof Dispatcher) {
            return $this->dispatcher->triggerEvent($event, $args);
        }

        return null;
    }

    /**
     * Enqueue a system message.
     *
     * @param string $msg  The message to enqueue.
     * @param string $type The message type. Default is message.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     */
    public function enqueueMessage($msg, $type = 'message')
    {
        $this->getSession()->getFlashBag()->add($type, $msg);

        return $this;
    }

    /**
     * Clear the system message queue.
     *
     * @return void
     *
     * @since   1.0
     */
    public function clearMessageQueue()
    {
        $this->getSession()->getFlashBag()->clear();
    }

    /**
     * Get the system message queue.
     *
     * @return array The system message queue.
     *
     * @since   1.0
     */
    public function getMessageQueue()
    {
        return $this->getSession()->getFlashBag()->peekAll();
    }

    /**
     * Set the system message queue for a given type.
     *
     * @param string $type    The type of message to set
     * @param mixed  $message Either a single message or an array of messages
     *
     * @return void
     *
     * @since   1.0
     */
    public function setMessageQueue($type, $message = '')
    {
        $this->getSession()->getFlashBag()->set($type, $message);
    }

    /**
     * Login or logout a user.
     *
     * @param User $user The user object.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function setUser(User $user = null)
    {
        $session = $this->getContainer()->fetch('session');

        if (is_null($user))
        {
            // Logout
            $this->user = null;
            $session->set('cobalt_user', $this->user);
        }
        else
        {
            // Login
            $user->isAdmin = true; // in_array($user->username, $this->get('acl.admin_users'));
            $this->user = $user;
            $session->set('cobalt_user', $this->user->serialize());
        }
    }

    /**
     * Get a user object.
     *
     * @param integer $id The user id or the current user.
     *
     * @return JUser
     *
     * @since   1.0
     */
    public function getUser($id = 0)
    {
        // Check if user isn't already loaded in chache array
        if ($id && isset($this->users[$id]))
        {
            return $this->users[$id];
        }

        // Create new User object
        $this->user = new User($this->container->fetch('db'));

        // Get user from session (if yes, user is logged in)
        if ($sessionUser = $this->getSession()->get('cobalt_user'))
        {
            $this->user->unserialize($sessionUser);
        }

        // If User ID is known, store it to cache array
        if ($this->user->get('id'))
        {
            $this->users[$id] = $this->user;
        }

        return $this->user;
    }

    /**
     * When user changes, refresh his/her data from database
     *
     * @return boolean
     */
    public function refreshUser()
    {
        $user = $this->getUser();

        if (!$user->get('id'))
        {
            // user must be logged in to be able to refresh itself
            return false;
        }

        $user->load($user->get('id'));
        $this->setUser($user);

        return true;
    }

    protected function detectRequestUri()
    {
        return str_replace('index.php', '', parent::detectRequestUri());
    }
}
