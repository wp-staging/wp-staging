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
namespace WPStaging\Vendor\Google\Service\Drive;

class Comment extends \WPStaging\Vendor\Google\Collection
{
    protected $collection_key = 'replies';
    /**
     * @var string
     */
    public $anchor;
    protected $authorType = \WPStaging\Vendor\Google\Service\Drive\User::class;
    protected $authorDataType = '';
    /**
     * @var string
     */
    public $content;
    /**
     * @var string
     */
    public $createdTime;
    /**
     * @var bool
     */
    public $deleted;
    /**
     * @var string
     */
    public $htmlContent;
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $kind;
    /**
     * @var string
     */
    public $modifiedTime;
    protected $quotedFileContentType = \WPStaging\Vendor\Google\Service\Drive\CommentQuotedFileContent::class;
    protected $quotedFileContentDataType = '';
    protected $repliesType = \WPStaging\Vendor\Google\Service\Drive\Reply::class;
    protected $repliesDataType = 'array';
    /**
     * @var bool
     */
    public $resolved;
    /**
     * @param string
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;
    }
    /**
     * @return string
     */
    public function getAnchor()
    {
        return $this->anchor;
    }
    /**
     * @param User
     */
    public function setAuthor(\WPStaging\Vendor\Google\Service\Drive\User $author)
    {
        $this->author = $author;
    }
    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }
    /**
     * @param string
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
    /**
     * @param string
     */
    public function setCreatedTime($createdTime)
    {
        $this->createdTime = $createdTime;
    }
    /**
     * @return string
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }
    /**
     * @param bool
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }
    /**
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
    /**
     * @param string
     */
    public function setHtmlContent($htmlContent)
    {
        $this->htmlContent = $htmlContent;
    }
    /**
     * @return string
     */
    public function getHtmlContent()
    {
        return $this->htmlContent;
    }
    /**
     * @param string
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @param string
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }
    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }
    /**
     * @param string
     */
    public function setModifiedTime($modifiedTime)
    {
        $this->modifiedTime = $modifiedTime;
    }
    /**
     * @return string
     */
    public function getModifiedTime()
    {
        return $this->modifiedTime;
    }
    /**
     * @param CommentQuotedFileContent
     */
    public function setQuotedFileContent(\WPStaging\Vendor\Google\Service\Drive\CommentQuotedFileContent $quotedFileContent)
    {
        $this->quotedFileContent = $quotedFileContent;
    }
    /**
     * @return CommentQuotedFileContent
     */
    public function getQuotedFileContent()
    {
        return $this->quotedFileContent;
    }
    /**
     * @param Reply[]
     */
    public function setReplies($replies)
    {
        $this->replies = $replies;
    }
    /**
     * @return Reply[]
     */
    public function getReplies()
    {
        return $this->replies;
    }
    /**
     * @param bool
     */
    public function setResolved($resolved)
    {
        $this->resolved = $resolved;
    }
    /**
     * @return bool
     */
    public function getResolved()
    {
        return $this->resolved;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(\WPStaging\Vendor\Google\Service\Drive\Comment::class, 'WPStaging\\Vendor\\Google_Service_Drive_Comment');
