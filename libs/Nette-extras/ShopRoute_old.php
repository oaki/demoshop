<?php
/**
 * Special route.
 *
 * @author     David Grudl
 * @package    Vitalita
 */
class ShopRoute_old extends NObject implements IRouter
{

        /**
         * Maps HTTP request to a PresenterRequest object.
         * @param  Nette\Web\IHttpRequest
         * @return PresenterRequest|NULL
         */
        public function match(IHttpRequest $context)
        {
        	
                if (!preg_match('#^/([a-z0-9]{1,3})/(.*?)/?$#', $context->getUri()->path, $matches)) {
                        return NULL;
                }


                $lang = $matches[1];
		$categories = $matches[2];
		$pom = explode("/", $categories);
		
		$last = end($pom);

		
                if (dibi::fetchSingle("
			SELECT
			    COUNT(*)
			FROM
			    category
			    JOIN category_lang USING(id_category)
			    JOIN [lang] USING(id_lang)
			WHERE iso=%s", $lang,"AND link_rewrite = %s",$last)
		) {
                        $presenter = 'Front:Eshop';

                } elseif (dibi::fetchSingle("SELECT COUNT(*) FROM menu_item WHERE lang = %s",$lang,"AND url_identifier=%s", $last)) {
                        $presenter = 'Front:List';
                } else {
                        return NULL;
                }
                // alternativa: použít jednu tabulku s páry URL -> jméno Presenteru
                // výhoda: jeden lookup místo (až) tří, neměřitelně vyšší rychlost ;)
                // nevýhoda: nutnost ji udržovat :-(

                // alternativa č.2: místo COUNT(*) načíst z DB celý záznam a předat v parametru presenteru
                // výhoda: stejně jej bude potřebovat
                // nevýhoda: nadstandardní závislost mezi routerem a presenterem

                $params = $context->getQuery();
//                $params['link_rewrite'] = $last;
		$params['lang'] = $lang;

                return new NPresenterRequest(
                        $presenter,
                        $context->getMethod(),
                        $params,
                        $context->getPost(),
                        $context->getFiles(),
                        array('secured' => $context->isSecured())
                );
        }

	

        /**
         * Constructs URL path from PresenterRequest object.
         * @param  Nette\Web\IHttpRequest
         * @param  PresenterRequest
         * @return string|NULL
         */
        public function constructUrl(NPresenterRequest $request, IHttpRequest $context)
        {
                // overime ze presenter je jeden ze podporovanych a existuje parameter 'id'
                static $presenters = array(
                        'Front:Eshop' => TRUE,
                        'Front:List' => TRUE,
                );

                if (!isset($presenters[$request->getPresenterName()])) {
                        return NULL;
                }

                $params = $request->getParams();
               
		$uri = '';
                $query = http_build_query($params, '', '&');
                if ($query !== '') $uri .= '?' . $query;

                return $uri;
        }

}