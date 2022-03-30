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

class Person extends \WPStaging\Vendor\Google\Collection
{
    protected $collection_key = 'userDefined';
    protected $addressesType = \WPStaging\Vendor\Google\Service\PeopleService\Address::class;
    protected $addressesDataType = 'array';
    /**
     * @var string
     */
    public $ageRange;
    protected $ageRangesType = \WPStaging\Vendor\Google\Service\PeopleService\AgeRangeType::class;
    protected $ageRangesDataType = 'array';
    protected $biographiesType = \WPStaging\Vendor\Google\Service\PeopleService\Biography::class;
    protected $biographiesDataType = 'array';
    protected $birthdaysType = \WPStaging\Vendor\Google\Service\PeopleService\Birthday::class;
    protected $birthdaysDataType = 'array';
    protected $braggingRightsType = \WPStaging\Vendor\Google\Service\PeopleService\BraggingRights::class;
    protected $braggingRightsDataType = 'array';
    protected $calendarUrlsType = \WPStaging\Vendor\Google\Service\PeopleService\CalendarUrl::class;
    protected $calendarUrlsDataType = 'array';
    protected $clientDataType = \WPStaging\Vendor\Google\Service\PeopleService\ClientData::class;
    protected $clientDataDataType = 'array';
    protected $coverPhotosType = \WPStaging\Vendor\Google\Service\PeopleService\CoverPhoto::class;
    protected $coverPhotosDataType = 'array';
    protected $emailAddressesType = \WPStaging\Vendor\Google\Service\PeopleService\EmailAddress::class;
    protected $emailAddressesDataType = 'array';
    /**
     * @var string
     */
    public $etag;
    protected $eventsType = \WPStaging\Vendor\Google\Service\PeopleService\Event::class;
    protected $eventsDataType = 'array';
    protected $externalIdsType = \WPStaging\Vendor\Google\Service\PeopleService\ExternalId::class;
    protected $externalIdsDataType = 'array';
    protected $fileAsesType = \WPStaging\Vendor\Google\Service\PeopleService\FileAs::class;
    protected $fileAsesDataType = 'array';
    protected $gendersType = \WPStaging\Vendor\Google\Service\PeopleService\Gender::class;
    protected $gendersDataType = 'array';
    protected $imClientsType = \WPStaging\Vendor\Google\Service\PeopleService\ImClient::class;
    protected $imClientsDataType = 'array';
    protected $interestsType = \WPStaging\Vendor\Google\Service\PeopleService\Interest::class;
    protected $interestsDataType = 'array';
    protected $localesType = \WPStaging\Vendor\Google\Service\PeopleService\Locale::class;
    protected $localesDataType = 'array';
    protected $locationsType = \WPStaging\Vendor\Google\Service\PeopleService\Location::class;
    protected $locationsDataType = 'array';
    protected $membershipsType = \WPStaging\Vendor\Google\Service\PeopleService\Membership::class;
    protected $membershipsDataType = 'array';
    protected $metadataType = \WPStaging\Vendor\Google\Service\PeopleService\PersonMetadata::class;
    protected $metadataDataType = '';
    protected $miscKeywordsType = \WPStaging\Vendor\Google\Service\PeopleService\MiscKeyword::class;
    protected $miscKeywordsDataType = 'array';
    protected $namesType = \WPStaging\Vendor\Google\Service\PeopleService\Name::class;
    protected $namesDataType = 'array';
    protected $nicknamesType = \WPStaging\Vendor\Google\Service\PeopleService\Nickname::class;
    protected $nicknamesDataType = 'array';
    protected $occupationsType = \WPStaging\Vendor\Google\Service\PeopleService\Occupation::class;
    protected $occupationsDataType = 'array';
    protected $organizationsType = \WPStaging\Vendor\Google\Service\PeopleService\Organization::class;
    protected $organizationsDataType = 'array';
    protected $phoneNumbersType = \WPStaging\Vendor\Google\Service\PeopleService\PhoneNumber::class;
    protected $phoneNumbersDataType = 'array';
    protected $photosType = \WPStaging\Vendor\Google\Service\PeopleService\Photo::class;
    protected $photosDataType = 'array';
    protected $relationsType = \WPStaging\Vendor\Google\Service\PeopleService\Relation::class;
    protected $relationsDataType = 'array';
    protected $relationshipInterestsType = \WPStaging\Vendor\Google\Service\PeopleService\RelationshipInterest::class;
    protected $relationshipInterestsDataType = 'array';
    protected $relationshipStatusesType = \WPStaging\Vendor\Google\Service\PeopleService\RelationshipStatus::class;
    protected $relationshipStatusesDataType = 'array';
    protected $residencesType = \WPStaging\Vendor\Google\Service\PeopleService\Residence::class;
    protected $residencesDataType = 'array';
    /**
     * @var string
     */
    public $resourceName;
    protected $sipAddressesType = \WPStaging\Vendor\Google\Service\PeopleService\SipAddress::class;
    protected $sipAddressesDataType = 'array';
    protected $skillsType = \WPStaging\Vendor\Google\Service\PeopleService\Skill::class;
    protected $skillsDataType = 'array';
    protected $taglinesType = \WPStaging\Vendor\Google\Service\PeopleService\Tagline::class;
    protected $taglinesDataType = 'array';
    protected $urlsType = \WPStaging\Vendor\Google\Service\PeopleService\Url::class;
    protected $urlsDataType = 'array';
    protected $userDefinedType = \WPStaging\Vendor\Google\Service\PeopleService\UserDefined::class;
    protected $userDefinedDataType = 'array';
    /**
     * @param Address[]
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }
    /**
     * @return Address[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }
    /**
     * @param string
     */
    public function setAgeRange($ageRange)
    {
        $this->ageRange = $ageRange;
    }
    /**
     * @return string
     */
    public function getAgeRange()
    {
        return $this->ageRange;
    }
    /**
     * @param AgeRangeType[]
     */
    public function setAgeRanges($ageRanges)
    {
        $this->ageRanges = $ageRanges;
    }
    /**
     * @return AgeRangeType[]
     */
    public function getAgeRanges()
    {
        return $this->ageRanges;
    }
    /**
     * @param Biography[]
     */
    public function setBiographies($biographies)
    {
        $this->biographies = $biographies;
    }
    /**
     * @return Biography[]
     */
    public function getBiographies()
    {
        return $this->biographies;
    }
    /**
     * @param Birthday[]
     */
    public function setBirthdays($birthdays)
    {
        $this->birthdays = $birthdays;
    }
    /**
     * @return Birthday[]
     */
    public function getBirthdays()
    {
        return $this->birthdays;
    }
    /**
     * @param BraggingRights[]
     */
    public function setBraggingRights($braggingRights)
    {
        $this->braggingRights = $braggingRights;
    }
    /**
     * @return BraggingRights[]
     */
    public function getBraggingRights()
    {
        return $this->braggingRights;
    }
    /**
     * @param CalendarUrl[]
     */
    public function setCalendarUrls($calendarUrls)
    {
        $this->calendarUrls = $calendarUrls;
    }
    /**
     * @return CalendarUrl[]
     */
    public function getCalendarUrls()
    {
        return $this->calendarUrls;
    }
    /**
     * @param ClientData[]
     */
    public function setClientData($clientData)
    {
        $this->clientData = $clientData;
    }
    /**
     * @return ClientData[]
     */
    public function getClientData()
    {
        return $this->clientData;
    }
    /**
     * @param CoverPhoto[]
     */
    public function setCoverPhotos($coverPhotos)
    {
        $this->coverPhotos = $coverPhotos;
    }
    /**
     * @return CoverPhoto[]
     */
    public function getCoverPhotos()
    {
        return $this->coverPhotos;
    }
    /**
     * @param EmailAddress[]
     */
    public function setEmailAddresses($emailAddresses)
    {
        $this->emailAddresses = $emailAddresses;
    }
    /**
     * @return EmailAddress[]
     */
    public function getEmailAddresses()
    {
        return $this->emailAddresses;
    }
    /**
     * @param string
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;
    }
    /**
     * @return string
     */
    public function getEtag()
    {
        return $this->etag;
    }
    /**
     * @param Event[]
     */
    public function setEvents($events)
    {
        $this->events = $events;
    }
    /**
     * @return Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }
    /**
     * @param ExternalId[]
     */
    public function setExternalIds($externalIds)
    {
        $this->externalIds = $externalIds;
    }
    /**
     * @return ExternalId[]
     */
    public function getExternalIds()
    {
        return $this->externalIds;
    }
    /**
     * @param FileAs[]
     */
    public function setFileAses($fileAses)
    {
        $this->fileAses = $fileAses;
    }
    /**
     * @return FileAs[]
     */
    public function getFileAses()
    {
        return $this->fileAses;
    }
    /**
     * @param Gender[]
     */
    public function setGenders($genders)
    {
        $this->genders = $genders;
    }
    /**
     * @return Gender[]
     */
    public function getGenders()
    {
        return $this->genders;
    }
    /**
     * @param ImClient[]
     */
    public function setImClients($imClients)
    {
        $this->imClients = $imClients;
    }
    /**
     * @return ImClient[]
     */
    public function getImClients()
    {
        return $this->imClients;
    }
    /**
     * @param Interest[]
     */
    public function setInterests($interests)
    {
        $this->interests = $interests;
    }
    /**
     * @return Interest[]
     */
    public function getInterests()
    {
        return $this->interests;
    }
    /**
     * @param Locale[]
     */
    public function setLocales($locales)
    {
        $this->locales = $locales;
    }
    /**
     * @return Locale[]
     */
    public function getLocales()
    {
        return $this->locales;
    }
    /**
     * @param Location[]
     */
    public function setLocations($locations)
    {
        $this->locations = $locations;
    }
    /**
     * @return Location[]
     */
    public function getLocations()
    {
        return $this->locations;
    }
    /**
     * @param Membership[]
     */
    public function setMemberships($memberships)
    {
        $this->memberships = $memberships;
    }
    /**
     * @return Membership[]
     */
    public function getMemberships()
    {
        return $this->memberships;
    }
    /**
     * @param PersonMetadata
     */
    public function setMetadata(\WPStaging\Vendor\Google\Service\PeopleService\PersonMetadata $metadata)
    {
        $this->metadata = $metadata;
    }
    /**
     * @return PersonMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
    /**
     * @param MiscKeyword[]
     */
    public function setMiscKeywords($miscKeywords)
    {
        $this->miscKeywords = $miscKeywords;
    }
    /**
     * @return MiscKeyword[]
     */
    public function getMiscKeywords()
    {
        return $this->miscKeywords;
    }
    /**
     * @param Name[]
     */
    public function setNames($names)
    {
        $this->names = $names;
    }
    /**
     * @return Name[]
     */
    public function getNames()
    {
        return $this->names;
    }
    /**
     * @param Nickname[]
     */
    public function setNicknames($nicknames)
    {
        $this->nicknames = $nicknames;
    }
    /**
     * @return Nickname[]
     */
    public function getNicknames()
    {
        return $this->nicknames;
    }
    /**
     * @param Occupation[]
     */
    public function setOccupations($occupations)
    {
        $this->occupations = $occupations;
    }
    /**
     * @return Occupation[]
     */
    public function getOccupations()
    {
        return $this->occupations;
    }
    /**
     * @param Organization[]
     */
    public function setOrganizations($organizations)
    {
        $this->organizations = $organizations;
    }
    /**
     * @return Organization[]
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }
    /**
     * @param PhoneNumber[]
     */
    public function setPhoneNumbers($phoneNumbers)
    {
        $this->phoneNumbers = $phoneNumbers;
    }
    /**
     * @return PhoneNumber[]
     */
    public function getPhoneNumbers()
    {
        return $this->phoneNumbers;
    }
    /**
     * @param Photo[]
     */
    public function setPhotos($photos)
    {
        $this->photos = $photos;
    }
    /**
     * @return Photo[]
     */
    public function getPhotos()
    {
        return $this->photos;
    }
    /**
     * @param Relation[]
     */
    public function setRelations($relations)
    {
        $this->relations = $relations;
    }
    /**
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }
    /**
     * @param RelationshipInterest[]
     */
    public function setRelationshipInterests($relationshipInterests)
    {
        $this->relationshipInterests = $relationshipInterests;
    }
    /**
     * @return RelationshipInterest[]
     */
    public function getRelationshipInterests()
    {
        return $this->relationshipInterests;
    }
    /**
     * @param RelationshipStatus[]
     */
    public function setRelationshipStatuses($relationshipStatuses)
    {
        $this->relationshipStatuses = $relationshipStatuses;
    }
    /**
     * @return RelationshipStatus[]
     */
    public function getRelationshipStatuses()
    {
        return $this->relationshipStatuses;
    }
    /**
     * @param Residence[]
     */
    public function setResidences($residences)
    {
        $this->residences = $residences;
    }
    /**
     * @return Residence[]
     */
    public function getResidences()
    {
        return $this->residences;
    }
    /**
     * @param string
     */
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;
    }
    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }
    /**
     * @param SipAddress[]
     */
    public function setSipAddresses($sipAddresses)
    {
        $this->sipAddresses = $sipAddresses;
    }
    /**
     * @return SipAddress[]
     */
    public function getSipAddresses()
    {
        return $this->sipAddresses;
    }
    /**
     * @param Skill[]
     */
    public function setSkills($skills)
    {
        $this->skills = $skills;
    }
    /**
     * @return Skill[]
     */
    public function getSkills()
    {
        return $this->skills;
    }
    /**
     * @param Tagline[]
     */
    public function setTaglines($taglines)
    {
        $this->taglines = $taglines;
    }
    /**
     * @return Tagline[]
     */
    public function getTaglines()
    {
        return $this->taglines;
    }
    /**
     * @param Url[]
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }
    /**
     * @return Url[]
     */
    public function getUrls()
    {
        return $this->urls;
    }
    /**
     * @param UserDefined[]
     */
    public function setUserDefined($userDefined)
    {
        $this->userDefined = $userDefined;
    }
    /**
     * @return UserDefined[]
     */
    public function getUserDefined()
    {
        return $this->userDefined;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(\WPStaging\Vendor\Google\Service\PeopleService\Person::class, 'WPStaging\\Vendor\\Google_Service_PeopleService_Person');
