<?php

namespace Drupal\quizers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class QuizEditform extends FormBase {
  /**
   * Adding variable.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Adding variable.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, RequestStack $request) {
    $this->database = $database;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_edit_form';
  }

  /**
   * Implements add quiz form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $username = NULL) {
    $quiz_name = $this->request->getCurrentRequest()->get('id');
    // $database = \Drupal::service('database');
    $query = $this->database->select('quiz_table', 'qt');
    $query->fields('qt', [
      'id', 'quiz_name', 'question', 'ans0', 'ans1', 'ans2', 'ans3', 'ans4',
    ]);
    $query->condition('quiz_name', $quiz_name, '=');
    $results = $query->execute()->fetchAll();

    $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    $j = 1;
    foreach ($results as $key => $result) {
      $form['tags']['tag_values'][$key]['id' . $key] = [
        '#type' => 'hidden',
        '#title' => 'question',
        '#default_value' => ('' . $result->id . ''),
      ];
      $form['tags']['tag_values'][$key]['que' . $key] = [
        '#type' => 'text_format',
        '#title' => 'question',
        '#default_value' => ('' . $result->question . ''),
      ];
      $form['tags']['tag_values'][$key]['ans'][$j++] = [
        '#type' => 'text_format',
        '#title' => 'ans',
        '#default_value' => ('' . $result->ans0 . ''),
      ];
      $form['tags']['tag_values'][$key]['ans'][$j++] = [
        '#type' => 'text_format',
        '#title' => 'ans',
        '#default_value' => ('' . $result->ans1 . ''),
      ];
      $form['tags']['tag_values'][$key]['ans'][$j++] = [
        '#type' => 'text_format',
        '#title' => 'ans',
        '#default_value' => ('' . $result->ans2 . ''),
      ];
      $form['tags']['tag_values'][$key]['ans'][$j++] = [
        '#type' => 'text_format',
        '#title' => 'ans',
        '#default_value' => ('' . $result->ans3 . ''),
      ];
      $form['tags']['tag_values'][$key]['ans'][$j++] = [
        '#type' => 'text_format',
        '#title' => 'right ans',
        '#default_value' => ('' . $result->ans4 . ''),
      ];
      $form['tags']['tag_values'][$key]['delete'] = [
        '#markup' => '<a href = "' . $base_path . 'mcq-delete?id=' . $result->id . '/' . $quiz_name . '" class="markup-button">Delete</a>',
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'submit',
    ];
    $form['back'] = [
      '#markup' => '<a href = "' . $base_path . 'add-quiz" class="markup-button">Back</a>',
    ];
    $form['add_more'] = [
      '#markup' => '<a href = "' . $base_path . 'form-quiz?quiz_name=' . $quiz_name . '" class="markup-button">Add more question</a>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // // $database = \Drupal::service('database');
    // $quiz_name = $this->request->getCurrentRequest()->get('quiz_name');
    $data = $form_state->getValues();
    $conta = [];
    $i = 1;
    foreach ($data as $values) {
      if ($values != 'submit') {
        $conta[$i++] = $values;

      }
      else {
        break;
      }
    }
    $mild = array_chunk($conta, 7);
    // kint($mild); exit;.
    foreach ($mild as $pair) {

      $this->database->update('quiz_table')

        ->fields([
          'question' => $pair[1]['value'],
          'ans0' => $pair[2]['value'],
          'ans1' => $pair[3]['value'],
          'ans2' => $pair[4]['value'],
          'ans3' => $pair[5]['value'],
          'ans4' => $pair[6]['value'],
        ])->condition('id', $pair[0], '=')->execute();
    }
  }

}
