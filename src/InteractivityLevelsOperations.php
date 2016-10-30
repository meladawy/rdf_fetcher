<?php

namespace Drupal\rdf_fetcher;


use \EasyRdf_Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Contains some operations to be used by the batch
 */
class InteractivityLevelsOperations {

  /**
   * array of fetched interactivity levels
   *
   * @var array
   */
  public static $levels = array();


  /**
  * Load RDF Data from external resource
  */
  public static function loadInteractivityLevels($resource_url, &$context) {
    // Interactivity levels
    $interactivity_levels = array() ;
    $counter = 0 ;
    // Initialize EasyRDF Object and load it
    $skos = new EasyRdf_Graph($resource_url);
    $skos->load();
    // filter resources by type
    $resources = $skos->allOfType("skos:Concept");
    // Loop over resources and fetch operations level
    foreach($resources as $resource) {
      $interactivity_levels[$counter]['name'] = !empty($resource->get("skos:prefLabel","literal", "en")) ? $resource->get("skos:prefLabel","literal", "en")->getValue() : "" ;
      $interactivity_levels[$counter]['uri'] = $resource->getUri();
      $interactivity_levels[$counter]['notation'] = !empty($resource->get("skos:notation")) ? $resource->get("skos:notation")->getValue() : "" ;
      $interactivity_levels[$counter]['definition'] = !empty($resource->get("skos:definition","literal", "en")) ? $resource->get("skos:definition","literal", "en")->getValue() : "" ;
      $interactivity_levels[$counter]['identifier'] = !empty($resource->get("dc:identifier")) ? $resource->get("dc:identifier")->getValue() : "" ;
      $counter++ ;
    }
    $context['message'] = t("Fetching data...2ol yarab");
    // Pass fetched data to other operations
    $context['results']['levels'] =  $interactivity_levels ;
  }


  /**
  * Create list of levels from fetched RDF file
  */
  public static function createInteractivityLevels($vocabulary_type, &$context) {
    $levels = $context['results']['levels'];
    $created_levels = 0 ;
    $updated_levels = 0 ;
    foreach($levels as $level) {
      // Make sure that level is not exist in our database
      // if its exist i'll update it, its important to update existing terms instead of re-creating it as there
      // are already some contents should be in relationg with those terms
      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('vid', $vocabulary_type);
      $query->condition('name', $level['name']);
      $tids = $query->execute();
      // If term exist then update it
      if($tids) {
        $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
        foreach($terms as $term) {
          $term->set("field_identifier", $level['identifier']) ;
          $term->set("field_skos_definition", $level['definition']) ;
          $term->set("field_skos_notation", $level['notation']) ;
          $term->set("field_uri", $level['uri']) ;
          $term->save() ;
          $updated_levels++;
        }
        $context['message'] = t("Creating new levels..");
      }else {
        $term = \Drupal\taxonomy\Entity\Term::create(array(
          'name' => $level['name'],
          'vid' => $vocabulary_type,
          'field_identifier' => array(
            'value' => $level['identifier'] ,
          ),
          'field_skos_definition' => array(
            'value' => $level['definition'] ,
          ),
          'field_skos_notation' => array(
            'value' => $level['notation'] ,
          ),
          'field_uri' => array(
            'uri' => $level['uri'] ,
          ),
        ));
        $term->save() ;
        $created_levels++;
        $context['message'] = t("Updating duplicated levels..");
      }
    }
    if($created_levels) {
      drupal_set_message(t('@num created levels', array('@num' => $created_levels))) ;
    }
    if($updated_levels) {
      drupal_set_message(t('@num updated levels', array('@num' => $updated_levels))) ;
    }
  }

  /**
  * This function to delete all terms that are not included in the RDF file
  */
  public static function deleteAdditionalLevels($vocabulary_type, &$context) {
    $levels = $context['results']['levels'];
    $context['message'] = t("Cleaning extra levels..");
    $deleted_levels = 0 ;
    // Store all level names in array
    $level_names = array() ;
    // Get interactivities name in array
    foreach($levels  as $level) {
      $level_names[] = $level['name'] ;
    }
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vocabulary_type);
    $query->condition('name', $level_names , 'NOT IN');
    $tids = $query->execute();
    $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
    foreach($terms as $term) {
      $term->delete() ;
      $deleted_levels++ ;
    }
    if($deleted_levels) {
      drupal_set_message(t('@num deleted levels', array('@num' => $deleted_levels))) ;
    }

  }


  /**
  * Callback function that ill be called by batch api after finalizing all the operations
  */
  public static function finishOperationsCallback($success, $results, $operations) {
    if(!$success) {
      $message = t('Finished with an error..i don know what it could be actually !');
    }
    return new RedirectResponse(\Drupal::url('<front>'));
  }
}
