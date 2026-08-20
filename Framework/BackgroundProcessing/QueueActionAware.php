<?php







namespace WPStaging\Framework\BackgroundProcessing;






trait QueueActionAware
{






    private $queueCurrentAction;








    public function setCurrentAction($action = null)
    {
        $this->queueCurrentAction = $action;
    }









    public function getCurrentAction()
    {
        return $this->queueCurrentAction;
    }
}
