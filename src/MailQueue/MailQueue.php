<?php

/**
 * @file
 * Contains Drupal\mail_queue\MailQueue\MailQueue.
 */

namespace Drupal\MailQueue;

use \EntityFieldQuery;

class MailQueue {
  private $id;
  private $lastShipment;
  /**
   * Possible values:
   *  -1 => Send on every cron run.
   *   0 => Never sent or manual sent.
   *   1 => Send every hour.
   *  24 => Send every day.
   * 168 => Send every week.
   */
  private $frequency;
  private $delay;

  public function __construct($id) {
    $this->id = $id;
    $entity = entity_email_type_load($id);
    // For delete operations, the entity will no longer exists. But we don't need it anyways.
    if ($entity) {
      $this->lastShipment = field_get_items('entity_email_type', $entity, 'mail_queue_sent')[0]['value'];
      $this->frequency = field_get_items('entity_email_type', $entity, 'mail_queue_frequency')[0]['value'];
      $this->delay = field_get_items('entity_email_type', $entity, 'mail_queue_delay')[0]['value'];
    }
  }

  public function send($force = FALSE) {
    if ($this->isShipable() || $force) {
      $emails = $this->getEmailsToSend();
      foreach ($emails as $email) {
        $email->send();
      }
      $this->updateLastShipment();
    }
  }

  private function getEmailsToSend() {
    $ids = array();
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'entity_email');
    $query->entityCondition('bundle', $this->id);
    $query->fieldCondition('mail_queue_is_sent', 'value', 0);
    if ($this->frequency) {
      $step = time() - $this->getTimeBetweenShipments();
      $query->propertyCondition('created', $step, '<');
    }
    $results = $query->execute();
    return entity_email_load_multiple($ids);
  }

  private function isShipable() {
    $isShipable = FALSE;

    $now = time();
    $lapse = $now - $this->lastShipment;

    $step = $this->getTimeBetweenShipments();

    if ($lapse != 0 && $lapse > $step) {
      $isShipable = TRUE;
    }

    return $isShipable;
  }

  private function getTimeBetweenShipments() {
    $step = 60*60 * $this->frequency;
    $step = $step + $step * $this->delay;
    return $step;
  }

  private function updateLastShipment() {
    $this->lastShipment = time();
    $entity = entity_email_type_load($this->id);
    $langcode = field_language('entity_email_type', $entity, 'mail_queue_sent');
    $entity->mail_queue_sent[$langcode][0]['value'] = $this->lastShipment;
    $entity->save();
  }

}

