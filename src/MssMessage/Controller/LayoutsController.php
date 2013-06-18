<?php
namespace MssMessage\Controller;
use Zend\Mvc\Controller\ActionController;

class LayoutsController extends ActionController
{
    public function indexAction()
    {
        $security = $this->getLocator()->get('mssuser_security_service');
        if (!$security->isRouteAllowed($this->getEvent())) {
            return $this->redirect()->toRoute('mssuser/login');
        }
        
        $user = $this->getLocator()->get('mssuser_user_service')->getAuthService()->getIdentity();
        $em   = $this->getEntityManager();
        $data = $em->getRepository('MssMessage\Entity\MessageLayout')
                   ->findGridArrayByCompany($user->getCompany()->getId());

        $svc  = $this->getLocator()->get('spiffydatatables_data_service');
        $data = $svc->format($data, array(
            'edit' => array(
                'type' => 'link',
                'options' => array(
                    'label' => 'Edit',
                    'link' => '/message/layouts/edit/%id%',
                )
            ),
            'trash' => array(
                'type' => 'jsClick',
                'options' => array(
                    'label' => 'Trash',
                    'onclick' => "alert('You clicked %name%');"
                )
            )
        ));

        return array('data' => $data);
    }

    /**
     * @TODO: Update this with JsonModel once ZF2 view refactor is complete.
     */
    public function imagesAction()
    {
        $user = $this->getLocator()
                     ->get('mssuser_user_service')
                     ->getAuthService()
                     ->getIdentity();
                     
        $em     = $this->getEntityManager();
        $images = $em->getRepository('MssFile\Entity\File')
                     ->findImagesArrayByCompany($user->getCompany()->getId());

        $list = array();
        foreach($images as $image) {
            $list[] = array($image->getName(), $image->getPublicUrl());
        }
        $js = 'var tinyMCEImageList = ' . json_encode($list) . ';';
        echo $js;
        exit;
    }
    
    public function addAction()
    {
        return $this->process();
    }

    public function editAction()
    {
        $match  = $this->getEvent()->getRouteMatch();
        $data   = $this->getEntityManager()->find(
            'MssMessage\Entity\MessageLayout', 
            $match->getParam('id')
        );
        
        return $this->process($data);
    }
    
    public function getEntityManager()
    {
        return $this->getLocator()->get('doctrine_em');
    }

    protected function process($data = null) 
    {
        $security = $this->getLocator()->get('mssuser_security_service');
        if (!$security->isRouteAllowed($this->getEvent())) {
            return $this->redirect()->toRoute('mssuser/login');
        }

        $request = $this->getRequest();
        $manager = $this->getLocator()->get('spiffyform_builder_orm', array(
            'definition' => 'mssmessage_layout_definition',
            'data'       => $data,
            'em'         => $this->getEntityManager()
        ));

        if ($request->isPost() && $manager->isValid($request->post())) {
            if ($manager->getForm()->getValue('cancel')) {
                $message = ':notice:Layout update cancelled';
            } else {
                $company = $this->getLocator()->get('mssuser_user_service')->getAuthService()->getIdentity()->getCompany();
                $manager->getData()->setCompany($this->getEntityManager()->getReference('MssCompany\Entity\Company', $company->getId()));
                $this->getEntityManager()->persist($manager->getData());
                $this->getEntityManager()->flush();

                $message = $data ? ':success:Layout updated' : ':success:Layout added';
            }

            $this->plugin('flashMessenger')
                 ->setNamespace('spiffy_notify')
                 ->addMessage($message);

            return $this->redirect()->toRoute('mssmessage/layouts');
        }
        
        return array('manager' => $manager);
    }
}