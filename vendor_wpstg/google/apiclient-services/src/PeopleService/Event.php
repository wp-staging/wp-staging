<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace WPStaging\Vendor\Google\Service\PeopleService;

class Event extends \WPStaging\Vendor\Google\Model
{
    protected $dateType = \WPStaging\Vendor\Google\Service\PeopleService\Date::class;
    protected $dateDataType = '';
    /**
     * @var string
     */
    public $formattedType;
    protected $metadataType = \WPStaging\Vendor\Google\Service\PeopleService\FieldMetadata::class;
    protected $metadataDataType = '';
    /**
     * @var string
     */
    public $type;
    /**
     * @param Date
     */
    public function setDate(\WPStaging\Vendor\Google\Service\PeopleService\Date $date)
    {
        $this->date = $date;
    }
    /**
     * @return Date
     */
    public function getDate()
    {
        return $this->date;
    }
    /**
     * @param string
     */
    public function setFormattedType($formattedType)
    {
        $this->formattedType = $formattedType;
    }
    /**
     * @return string
     */
    public function getFormattedType()
    {
        return $this->formattedType;
    }
    /**
     * @param FieldMetadata
     */
    public function setMetadata(\WPStaging\Vendor\Google\Service\PeopleService\FieldMetadata $metadata)
    {
        $this->metadata = $metadata;
    }
    /**
     * @return FieldMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
    /**
     * @param string
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(\WPStaging\Vendor\Google\Service\PeopleService\Event::class, 'WPStaging\\Vendor\\Google_Service_PeopleService_Event');
