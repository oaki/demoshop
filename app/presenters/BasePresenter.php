<?php

abstract class BasePresenter extends NPresenter
{

    /** @persistent */
    public $lang = 'sk';


    public $id_lang;

    public function startup()
    {

        $this->id_lang = Lang::convertIsoToId($this->lang);

        parent::startup();

        //spojenie parametrov z config a databazy
        $this->context->parameters += $this->getService('Setting')->fetchPairsWithGroup();
//		dump($this->context->parameters); 
        $this->template->langs = Lang::getAll();

//		dump($this->context->parameters);exit;

        $db_config = NEnvironment::getConfig('database');

        if (!defined('TABLE_ACL')) {
            define('TABLE_ACL', $db_config->tables->acl);

            define('TABLE_PRIVILEGES', $db_config->tables->acl_privileges);
            define('TABLE_RESOURCES', $db_config->tables->acl_resources);
            define('TABLE_ROLES', $db_config->tables->acl_roles);
            define('TABLE_USERS', $db_config->tables->users);
            define('TABLE_USERS_INFO', $db_config->tables->users_info);
            define('TABLE_USERS_COUNTRY', $db_config->tables->users_country);
            define('TABLE_USERS_ROLES', $db_config->tables->users_roles);

            $acl_config = NEnvironment::getConfig('acl');
            define('ACL_RESOURCE', $acl_config->resource);
            define('ACL_PRIVILEGE', $acl_config->privilege);
            define('ACL_CACHING', $acl_config->cache);
            define('ACL_PROG_MODE', $acl_config->programmer_mode);
        }

        $this->template->prog_mode = (ACL_PROG_MODE ? true : false);


        $this->template->user = $this->user;

        if ($this->user->isLoggedIn()) {
            $this->template->identity = $this->user->getIdentity();
        }

        // cache
        if (ACL_CACHING) {
            $cache = NEnvironment::getCache();

            if (!isset($cache['gui_acl'])) {
                $cache->save('gui_acl', new Acl(), array(
                    'files' => array(APP_DIR . '/config.ini'),
                ));
            }

            $this->user->setAuthorizator($cache['gui_acl']);
        } else
            $this->user->setAuthorizator(new Acl());


    }


    /**
     * Check if the user has permissions to enter this section.
     * If not, then it is redirected.
     *
     */
    public function checkAccess()
    {

        // if the user is not allowed access to the ACL, then redirect him
//		echo ACL_RESOURCE;
//		echo '<br >'.ACL_PRIVILEGE;exit;
        if (!$this->user->isAllowed('cms', 'edit')) {
            // @todo change redirect to login page
            $this->redirect('Denied:');
        }
    }


    public function createTemplate($class = NULL)
    {


        /*
         * Translator
         */

        $t = $this->context->translator;
        $t->setLang($this->lang);

        $template = parent::createTemplate($class);

        $template->setTranslator($t);

        $template->registerHelperLoader('FormatHelper::loadHelper');

        $template->registerHelperLoader('ImageHelper::loadHelper');

        $template->registerHelperLoader('DeclineHelper::loadHelper');


        return $template;

    }


    protected function createComponent($name)
    {

        switch ($name) {

            case 'help':
                return new HelpControl();
                break;

            case 'header':

                $header = new MyHeaderControl;

                $header->setDocType(HeaderControl::HTML_5);
                $header->setLanguage($this->lang);

//				$header->setTitle('Example title');

                // facebook xml namespace
//				$header->htmlTag->attrs['xmlns:fb'] = 'http://www.facebook.com/2008/fbml';

                $header->setTitleSeparator(' ')
                    ->setTitlesReverseOrder(true)
                    ->setRobots('index,follow')
                    ->setMetaTag('doc-type', 'Web Page')
                    ->setMetaTag('doc-class', 'Published')
                    ->setMetaTag('doc-rights', 'Copywritten Work')
//					->addKeywords(array('two', 'three'))
//					->setDescription('Our example site')
                    ->setRobots('index,follow') //of course ;o)
//					->addRssChannel('News', 'Feed:rss')
                    //->addRssChannel('Comments', 'Rss:comments')
                ;

                //CssLoader
                $css = $header['css'];
                $css->sourcePath = WWW_DIR . "/css";
                $css->tempUri = NEnvironment::getVariable("webloaderTempUri");
                $css->tempPath = NEnvironment::getVariable("webloaderTempPath");


//				$css->addFile('../jscripts/jquery/custom-theme/jquery-ui-1.8.5.custom.css');
                $css->setJoinFiles(false);

                if (NEnvironment::isProduction()) {
                    $css->filters [] = array($this, 'packCss');
//					$css->setJoinFiles(true);
                }

                //JavascriptLoader
                $js = $header['js'];
                $js->setJoinFiles(false);

                $js->sourcePath = WWW_DIR . "/jscripts";

                $js->tempUri = NEnvironment::getVariable("webloaderTempUri");
                $js->tempPath = NEnvironment::getVariable("webloaderTempPath");
//				$js->joinFiles = NEnvironment::isProduction();

                $js->addFiles(array(
//					'jquery/jquery-1.11.1.min.js',
                    'jquery/jquery-1.7.2.min.js',
                    'jquery/jquery-ui-1.8.5.custom.min.js',
                    'jquery/jquery.ui.datepicker-sk.js',
                    'jquery/jquery.nette.js',
                    'jquery/jquery.ajaxform.js',
                    'jquery/netteForms.js',
                ));
                // v production módu zapne minimalizaci javascriptu

//				if (NEnvironment::isProduction()) {
//			        $js->filters[] = array($this, "noPack");
//			    }

                return $header;
                break;

            default :
                return parent::createComponent($name);
                break;
        }
    }


    public function noPack($code)
    {
        return $code;
    }

    public function packCss($code)
    {
        return $code;
        $packer = new CssMin();

        return $packer->minify($code, array("remove-last-semicolon"));
    }
}



