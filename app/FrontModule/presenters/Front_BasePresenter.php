<?php

/**
 * Front_BasePresenter
 *
 * @category  Wienerboerse
 * @author    Pavol Bincik <pavol.bincik@salesxp.com>
 * @copyright salesXp GmbH
 * @version   Release: @package_version@
 * @link      http://salesxp.com
 */
abstract class Front_BasePresenter extends BasePresenter
{

    public function startup()
    {
        parent::startup();

        if ($this->context->parameters['SHOP_ENABLE'] != 1) {
            $this->redirect(':Front:Underconstruction:default');
        }

    }

    protected function beforeRender()
    {
//		dump($this->context);
        $this->template->id_category  = null;
        $this->template->id_menu_item = null;

        $this['header']['css']->addFile('reset.css');
//		$this['header']['css']->addFile('default.css');
        $this['header']['css']->addFile('h.css');

        $this['header']['css']->addFile('article.css');
        $this['header']['css']->addFile('attachment.css');

        $this['header']['css']->addFile('gallery.css');
        $this['header']['css']->addFile('modal-dialog.css');
        $this['header']['css']->addFile('paginator.css');
        $this['header']['css']->addFile('components/ContactForm/contact-form.css');
        $this['header']['css']->addFile('delivery-form.css');
        $this['header']['css']->addFile('msg.css');
        $this['header']['css']->addFile('cart.css');
        $this['header']['css']->addFile('sharepost.css');
        $this['header']['css']->addFile('../jscripts/jquery/custom-theme/jquery-ui-1.8.13.custom.css');

        $this['header']['css']->addFile('/screen.css');
        $this['header']['css']->addFile('/login-dialog.css');
        $this['header']['css']->addFile('/flash-msg.css');

        // sekcia pre adminou, ktory maju moznost upravy aj na fronte
//		if($this->user->isLoggedIn() AND $this->user->isAllowed('cms','edit')){
//			$this['header']['css']->addFile('adminBar/admin-bar.css');
//		}

//		<link href='http://fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css'>

//		$this['header']->addCssFile('http://fonts.googleapis.com/css?family=Oswald');

        $header = $this->getHttpRequest()->getHeaders();

        if (strstr(@$header['user-agent'], 'MSIE 7')) {
            $this['header']['css']->addFile('ie.css');
        }

        $this['header']['js']->addFile('jquery/dropdown/js/superfish.js');
        $this['header']['css']->addFile('../jscripts/jquery/dropdown/css/superfish.css');

//		$this['header']['css']->addFile('../jscripts/jquery/jquery.treeview/jquery.treeview.css');

        $this['header']['js']->addFile('jquery/prettyPhoto/js/jquery.prettyPhoto.js');
        $this['header']['css']->addFile('../jscripts/jquery/prettyPhoto/css/prettyPhoto.css');

        $this['header']['js']->addFile('jquery/In-Field-Labels/src/jquery.infieldlabel.min.js');

        $this['header']['js']->addFile('jquery/relatedselects/jquery.relatedselects.js');
//		$this['header']['css']->addFile('../jscripts/jquery/bubblepopup/jquery.bubblepopup.v2.3.1.css');
//		$this['header']['js']->addFile('jquery/bubblepopup/jquery.bubblepopup.v2.3.1.min.js');

        $this['header']['js']->addFile('jquery/orbit_slider/jquery.orbit-1.2.3.min.js');
        $this['header']['css']->addFile('../jscripts/jquery/orbit_slider/orbit-1.2.3.css');

        $this['header']['js']->addFile('jquery/jquery_news_ticker/includes/jquery.ticker.js');
        $this['header']['css']->addFile('../jscripts/jquery/jquery_news_ticker/styles/ticker-style.css');

        $this['header']['js']->addFile('loader.js');

        if ($this->context->parameters['GOOGLE_ANALYTICS']['ID'] != '') {
            $this['header']->addJscript(
                "
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '" . $this->context->parameters['GOOGLE_ANALYTICS']['ID'] . "']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
");
        }

        $this->template->eshop_category = array();
        $this->template->eshop_category = CategoryModel::getTreeAssoc($this->id_lang);

        $page = $this->getService('Page');

        $this->template->mainmenu = $page->getAssoc($this->id_lang);

        /*
         * zistenie ci je nieco v akcii ak nieje nezobrazi sa tlacidlo
         * - bude sa cachovat spolu s modelom product
         */

        $count = ProductModel::loadCache('sale_count');
//        var_dump($count);
//        exit;
        if ($count === null) {
            $count = ProductModel::saveCache('sale_count',
                count(Front_SearchPresenter::getQuery()->where('product.sale = 1')->groupBy('id_product'))
            );
        }

        $this->template->is_sale = ($count > 0) ? true : false;

    }

    public function searchFormSubmitted($form)
    {
        $this->redirect("Search:default", array('q' => $form->values->q));
    }

    protected function createComponent($name)
    {

        switch ($name) {
            case 'article' :
                return new ArticleControl ();
                break;

            case 'questionToSeller' :
                $f = new QuestionToSeller ();
                $f->setDefaults(array('link' => $_SERVER['HTTP_HOST'] . $this->link('this')));

                return $f;
                break;

            case 'searchform':
                $f = new MyForm;

                $f->addText('q', 'hľadané slovo / výraz')
                    ->getControlPrototype()->class = 'no-border';
                //->addRule( NForm::FILLED, 'Hľadané slovo musí byť vyplnené');
                $f->addSubmit('btn', 'Vyhľadať')
                    ->getControlPrototype()->class = 'no-border';
                $f->onSuccess[]                    = array($this, 'searchFormSubmitted');

                $f->setDefaults(array('q' => $this->getParam('q')));

                return $f;
                break;

            case 'poll' :
                $p = new PollControl ();

                return $p;
                break;

            /*
             * Pomocka pre pohyb admina po Front
             */
            case 'adminBar' :
                return new AdminBarControl();
                break;

            /*
             * Sluzi na zobrazenie vypredajovych poloziek
             * prislusenstva
             * noviniek
             */
            case 'productNewsSaleAvaiableTable' :
                return new ProductNewsSaleAvaiableTableControl();
                break;

            case 'Home' :
                return new SlideshowControl();
                break;

            case 'breadcrumb' :
                return new BreadcrumbControl();
                break;

            case 'quickFilter' :
                return new QuickFilterControl();
                break;

            case 'cart' :
                $cart = new CartControl;

                return $cart;
                break;

            case 'cartsmall' :
                $cart = new CartSmallControl;

                return $cart;
                break;

            case 'gmap' :
                return new GmapControl ();
                break;

            case 'newsletter' :
                return new NewsletterControl();
                break;

            case 'UserForm' :

                return new UserFormControl ();
                break;

            case 'LoginForm' :

                return new LoginControl();
                break;

            case 'userProfil' :

                return new UserProfilControl();
                break;

            case 'cartLogin' :
                return new CartLoginControl();
                break;

//			
//			case 'EshopProduct' :
//				$p = new EshopProductControl;
//				$p->invalidateControl();
//				return $p;
//				break;

//
//			case 'Redirect' :
//				return new RedirectControl ();
//				break;

            case 'ContactForm' :
                return new ContactFormControl ();
                break;

//			case 'comment' :
//				return new CommentControl ();
//				break;			

            case 'msg' :
                return new MsgControl ();
                break;

            case 'vizionWidget' :
                $widget = new VizionWidgetControl ();
                $widget->setWidgetService($this->getService('Widget'));

                return $widget;
                break;

            case 'userBaseForm':
                $form = UserModel::baseForm();

                $_convertor_lang_to_iso = array(
                    'sk' => 'SVK',
                    'cz' => 'CZE',
                    'de' => 'DEU',
                    'en' => 'GB',
                    'hu' => 'HUN',
                );

                $form->setDefaults(array('type' => 0, 'iso' => $_convertor_lang_to_iso[$this->lang], 'delivery_iso' => $_convertor_lang_to_iso[$this->lang],));

                return $form;
                break;

            default :
                return parent::createComponent($name);
                break;
        }
    }

}


