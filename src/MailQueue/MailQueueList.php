<?php

/**
 * @file
 * Contains Drupal\mail_queue\MailQueue\MailQueueList.
 */

namespace Drupal\MailQueue;

use Drupal\MailQueue\MailQueue;

define('MAIL_QUEUE_VAR', 'mail_queue_list');

class MailQueueList {
  private $mailQueues = array();

  /**
   * Returns the instance of the MailQueueList.
   */
  public static function Instance() {
    static $inst = NULL;
    if ($inst === NULL) {
      $class = __CLASS__;
      $inst = new $class;
    }
    return $inst;
  }

  /**
   * Adds a new queue to the list.
   */
  public function add($id) {
    $this->mailQueues[$id] = new MailQueue($id);
    $this->save();
  }

  /**
   * Deletes a queue from the list.
   */
  public function delete($id) {
    unset($this->mailQueues[$id]);
    $this->save();
  }

  /**
   * Sends all the mails that are ready.
   */
  public function send($force = FALSE) {
    foreach ($this->mailQueues as $mailQueue) {
      $mailQueue->send($force);
    }
  }

  /**
   * Static function to add a new queue to the list.
   */
  public static function addQueue($id) {
    $inst = MailQueueList::Instance();
    $inst->add($id);
  }

  /**
   * Static function to delete a queue from the list.
   */
  public static function deleteQueue($id) {
    $inst = MailQueueList::Instance();
    $inst->delete($id);
  }

  /**
   * Sends all the mails that are ready.
   */
  public static function sendMails($force = FALSE) {
    $inst = MailQueueList::Instance();
    $inst->send($force);
  }

  /**
   * Loads the list of MailQueues from the variable and creates the instance.
   */
  private function __construct() {
    $queues = variable_get(MAIL_QUEUE_VAR, array());
    foreach ($queues as $id) {
      // TODO this is failing. Fix me!
      $this->mailQueues[$id] = new MailQueue($id);
    }
  }

  /**
   * Uptates the control variable.
   */
  private function save() {
    variable_set(MAIL_QUEUE_VAR, array_keys($this->mailQueues));
  }

}

