<?php
class PageRoute_OLD extends NRoute
{
        // Parametry si nadefinuješ někde v konstruktoru,
        // případně si třídu podědíš a přepíšeš
        protected $column = 'url_identifier';
        protected $table = 'menu_item';


        public function match(IHttpRequest $httpRequest)
        {
//            print_r($httpRequest);
			$request = parent::match($httpRequest);

                if ($request === NULL)
                        return NULL;
				
                $params = $request->getParams();
//print_r($params);
                if (dibi::fetchSingle(
                                'SELECT
									COUNT(*)
								 FROM
									%n', $this->table,'
                                 WHERE									
									%n = %s', $this->column, $params['categories']
                        ) == 0){
                        return NULL;
				}

                return $request;
        }
}
