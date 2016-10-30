<?php

/**
 * @file
 * Contains \Drupal\rdf_fetcher\Form\SyncInteractivity.
 */

namespace Drupal\rdf_fetcher\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use \EasyRdf_Graph;
use Drupal\rdf_fetcher\InteractivityLevelsOperations;

/**
 * Class SyncInteractivity.
 *
 * @package Drupal\rdf_fetcher\Form
 */
class SyncInteractivity extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sync_interactivity';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Configuration variables can be changed from /config/install/rdf_fetcher.variables.yml
    $vocabulary_table = \Drupal::config('rdf_fetcher.variables')->get("vocabulary_type");
    $resource_url = \Drupal::config('rdf_fetcher.variables')->get("resource_url");

    $form['vocabulary_type'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('VID'),
      '#default_value' => $vocabulary_table,
      '#disabled' => TRUE,
      '#description' => $this->t('Vocabulary Name'),
    );
    $form['resource_url'] = array(
      '#type' => 'url',
      '#required' => TRUE,
      '#title' => $this->t('RDF Resouce'),
      '#default_value' => $resource_url,
      '#disabled' => TRUE,
      '#description' => $this->t('Resource RDF file'),
    );
    $form['sync'] = array(
      '#type' => 'submit',
      '#title' => $this->t('Sync'),
      '#value' => t('Synchronize'),
      '#description' => $this->t('To synchronize all interactivity levels with external RDF resources'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load Form "Resource URL" Value
    $resource_url = $form_state->getValue("resource_url") ;
    // Load "vocabulary type" value
    $vocabulary_type = $form_state->getValue("vocabulary_type") ;
    // Load Batch operations
    $batch = array(
     'title' => t('Importing Interactivity levels...'),
     'operations' => array(
       array(
         '\Drupal\rdf_fetcher\InteractivityLevelsOperations::loadInteractivityLevels',
         array($resource_url)
       ),
       array(
         '\Drupal\rdf_fetcher\InteractivityLevelsOperations::createInteractivityLevels',
         array($vocabulary_type)
       ),
       array(
         '\Drupal\rdf_fetcher\InteractivityLevelsOperations::deleteAdditionalLevels',
         array($vocabulary_type)
       ),
     ),
     'finished' => '\Drupal\rdf_fetcher\InteractivityLevelsOperations::finishOperationsCallback',
    );
    batch_set($batch);
  }

}
