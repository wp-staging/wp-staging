<?php

namespace WPStaging\Framework\Interfaces;

interface TransientInterface
{



    public function getTransientName();




    public function getExpiryTime();




    public function setTransient();




    public function getTransient();




    public function deleteTransient();
}
