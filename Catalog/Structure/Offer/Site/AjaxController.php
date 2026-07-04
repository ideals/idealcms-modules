<?php

namespace Catalog\Structure\Offer\Site;

class AjaxController extends \Ideal\Core\AjaxController
{
    /**
     * Устанавливаем
    */
    protected function setOfferAction()
    {
        session_start();
        if (!isset($_POST['good_id']) || !isset($_POST['ID'])) {
            print json_encode(['error' => 'fatal']);
            exit;
        }

        if (!is_numeric($_POST['ID'])) {
            exit;
        }

        $offerId = @ htmlspecialchars($_POST['ID']);
        $goodId = @ htmlspecialchars($_POST['good_id']);
        $_SESSION['offer']['ID'] =  $offerId;
        $_SESSION['offer']['good_id'] = $goodId;
        exit;
    }
}
