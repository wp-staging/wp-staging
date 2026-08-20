<?php

 
 

namespace WPStaging\Framework\Job\Dto;

use WPStaging\Framework\Traits\ArrayableTrait;

class TaskResponseDto extends AbstractDto
{
    use ArrayableTrait {
        toArray as traitToArray;
    }





    protected $excludeHydrate = ['last_msg', 'isForceSave', 'job_done'];

 
    protected $isRunning;

 
    protected $jobStatus;

 
    protected $percentage;

 
    protected $total;

 
    protected $step;

 
    protected $task;

 
    protected $job;

 
    protected $statusTitle;

 
    protected $messages;

 
    protected $jobId;




    public function toArray()
    {
        $data = $this->traitToArray();

        $lastMsg = null;
        if ($data['messages']) {
            $lastMsg = end($data['messages']);
        }

 
        $data['last_msg']    = $lastMsg;
        $data['isForceSave'] = true;
        $data['job_done']    = !$data['isRunning'];
        return $data;
    }




    public function addMessage($message)
    {
        if (!is_array($this->messages)) {
            $this->messages = [];
        }

        $this->messages[] = $message;
    }




    public function isRunning()
    {
        return $this->isRunning;
    }




    public function setIsRunning($isRunning)
    {
        $this->isRunning = $isRunning;
    }




    public function setJobStatus($jobStatus)
    {
        $this->jobStatus = $jobStatus;
    }




    public function getJobStatus()
    {
        return $this->jobStatus;
    }




    public function getPercentage()
    {
        return $this->percentage;
    }




    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
    }




    public function getTotal()
    {
        return $this->total;
    }




    public function setTotal($total)
    {
        $this->total = $total;
    }




    public function getStep()
    {
        return $this->step;
    }




    public function setStep($step)
    {
        $this->step = $step;
    }




    public function getTask()
    {
        return $this->task;
    }




    public function setTask($task)
    {
        $this->task = $task;
    }




    public function getJob()
    {
        return $this->job;
    }




    public function setJob($job)
    {
        $this->job = $job;
    }




    public function getStatusTitle()
    {
        return $this->statusTitle;
    }




    public function setStatusTitle($statusTitle)
    {
        $this->statusTitle = $statusTitle;
    }




    public function getMessages()
    {
        return $this->messages;
    }

    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }




    public function getJobId()
    {
        return $this->jobId;
    }




    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }
}
