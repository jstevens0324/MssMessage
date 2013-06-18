<?php
namespace MssMessage\Controller;
use Zend\Mvc\Controller\ActionController;

class MessageController extends ActionController
{
    public function indexAction()
    {
        $security = $this->getLocator()->get('mssuser_security_service');
        if (!$security->isRouteAllowed($this->getEvent())) {
            return $this->redirect()->toRoute('mssuser/login');
        }
        
        $user = $this->getLocator()->get('mssuser_user_service')->getAuthService()->getIdentity();
        $em   = $this->getEntityManager();
        $data = $em->getRepository('MssMessage\Entity\Message')
                   ->findGridArrayByCompany($user->getCompany()->getId());

        return array('data' => $data);
    }
    
    public function getEntityManager()
    {
        return $this->getLocator()->get('doctrine_em');
    }
}
