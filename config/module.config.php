<?php
return array(
    'di' => array(
        'instance' => array(
            'alias' => array(
                'mssmessage_sender_service' => 'MssMessage\Service\Sender',
            ),
            'mssmessage_sender_service' => array(
                'parameters' => array(
                    'em' => 'doctrine_em'
                )
            ),
            'orm_driver_chain' => array(
                'parameters' => array(
                    'drivers' => array(
                        'mssmessage_annotationdriver' => array(
                            'class'           => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                            'namespace'       => 'MssMessage\Entity',
                            'paths'           => array(__DIR__ . '/../src/MssMessage/Entity'),
                        ),
                    )
                )
            ),
        ),
    ),
);
