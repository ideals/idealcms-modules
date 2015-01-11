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
            print json_encode(array('error' => 'fatal'));
            exit;
        }
        if (!is_numeric($_POST['ID'])) {
            exit;
        }
        $offer_id = @ htmlspecialchars($_POST['ID']);
        $good_id = @ htmlspecialchars($_POST['good_id']);
        $_SESSION['offer']['ID'] =  $offer_id;
        $_SESSION['offer']['good_id'] = $good_id;
        exit;
    }
}
