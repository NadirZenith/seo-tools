<?php

namespace AppBundle\Controller;

use AppBundle\Form\SimpleRunType;
use Buzz\Browser;
use Buzz\Message\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $test_urls = array(
            'https://www.schweppes.es/tonica/nuestras-tonicas/classic/soda',
            'https://www.schweppes.es/cocteleria/jigger-cuchara-imperial-y-abridor-como-se-usan-correctamente'
        );

        $test_urls = array();

        $form = $this->createForm(SimpleRunType::class, null, array(
            'test_data' => implode("\n", $test_urls)
        ));

        $form->handleRequest($request);

        $status = array();
        $parsed_urls = array();
        if($form->isSubmitted() && $form->isValid()){
            set_time_limit(0);
            $data = $form->getData();
            $urls = array_values(explode("\r\n", $data['urls']));


//            $br = new \Buzz\Message\Request();
//            $br->s
            /** @var Browser $client */
            $browser = $this->get('buzz');
            $browser->getClient()->setTimeout(5000);
            foreach ($urls as $url){
                $url = trim($url);
                /** @var Response $response */
                $response = $browser->get($url);
                $status[$response->getStatusCode()][] = $url;

                $parsed_urls[] = $url;
                usleep(500);
            }


        }

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'form' => $form->createView(),
            'status' => $status,
            'urls' => $parsed_urls
        ]);
    }
}
