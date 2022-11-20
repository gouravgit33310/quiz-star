<?php

namespace Drupal\quizers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * {@inheritdoc}
 */
class Quizform extends FormBase {

  /**
   * Adding variable.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Adding variable.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messanger;

  /**
   * Adding variable.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, RequestStack $request, MessengerInterface $messanger) {
    $this->database = $database;
    $this->request = $request;
    $this->messanger = $messanger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_form';
  }

  /**
   * Add tags field.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $username = NULL) {

    $form['tags']['addtag'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOneTag'],
      '#weight' => 100,
      '#ajax' => [
        'callback' => '::updateTagCallback',
        'wrapper' => 'tagfields-wrapper',
        'method' => 'replace',
      ],
    ];
    $form['tags']['remtag'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove the last'),
      '#submit' => ['::remOneTag'],
      '#weight' => 100,
      '#ajax' => [
        'callback' => '::updateTagCallback',
        'wrapper' => 'tagfields-wrapper',
        'method' => 'replace',
      ],
    ];
    $form['tags']['tag_values'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="tagfields-wrapper">',
      '#suffix' => '</div>',
    ];
    $number_of_tags = $form_state->get('number_of_tags');
    if (empty($number_of_tags)) {
      $number_of_tags = 1;
      $form_state->set('number_of_tags', $number_of_tags);
    }
    for ($i = 0; $i < $number_of_tags; $i++) {
      $form['tags']['tag_values'][$i]['que'] = [
        '#type' => 'text_format',
        '#title' => 'question',
      ];

      for ($j = 0; $j <= 4; $j++) {
        if ($j < 4) {
          $form['tags']['tag_values'][$i]['ans'][$j] = [
            '#type' => 'text_format',
            '#title' => 'ans',
          ];
        }
        else {
          $form['tags']['tag_values'][$i]['ans'][$j] = [
            '#type' => 'text_format',
            '#title' => 'right ans',
            '#attributes' => ['class' => ['add-ans-fix']],
            
          ];
        }
      }
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'submit',
      ];
    }
    return $form;
  }


  /**
   * Add or Increment number of tags.
   */
  public function addOneTag(array &$form, FormStateInterface $form_state) {
    $number_of_tags = $form_state->get('number_of_tags');
    $form_state->set('number_of_tags', $number_of_tags + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Remove or Decrement number of tags.
   */
  public function remOneTag(array &$form, FormStateInterface $form_state) {
    $number_of_tags = $form_state->get('number_of_tags');
    $form_state->set('number_of_tags', $number_of_tags - 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Return the tag list (Form).
   */
  public function updateTagCallback(array &$form, FormStateInterface $form_state) {
    return $form['tags']['tag_values'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $quiz_name = $this->request->getCurrentRequest()->get('quiz_name');
    $results = $form_state->getValue('tag_values');

    $values = [];
    $count = 0;
    $qcount = 0;
    foreach ($results as $key => $result) {
      ++$qcount;
      $values[$key] = [
        'question' => $result['que']['value'],
      ];
      foreach ($result['ans'] as $ans) {
        if ($count <= 4) {
          $values[$key]['ans' . $count++ . ''] = $ans['value'];
        }
        else {
          $count = 0;
          $values[$key]['ans' . $count++ . ''] = $ans['value'];
        }
      }

    }

    $query = $this->database->insert('quiz_table')->fields([
      'quiz_name', 'question', 'ans0', 'ans1', 'ans2', 'ans3', 'ans4',
    ]);
    foreach ($values as $key => $record) { 
      $array_mer = array_merge($record, ['quiz_name' => $quiz_name]);
      $query->values($array_mer);
    }
    $query->execute();
    $query = $this->database->select('quiz_table', 'qt');
    $query->fields('qt', ['question']);
    $query->condition('quiz_name', $quiz_name, '=');
    $counts = $query->execute()->fetchAll();
    $qcount = count($counts);
    $this->database->update('quiz_type')
      ->fields([
        'question_count' => $qcount,
      ])
      ->condition('quiz_name', $quiz_name, '=')
      ->execute();
    $form_state->setRedirect('quizers.addquizform');
    $this->messanger->addMessage($this->t("Saved successfully"));

  }

}
