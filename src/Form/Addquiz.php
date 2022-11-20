<?php

namespace Drupal\quizers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Implements add quiz form.
 */
class Addquiz extends FormBase {

  /**
   * Adding variable.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

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
   * Adding variable.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messanger;

  /**
   * Implements cunstructor.
   */
  public function __construct(AccountInterface $account, Connection $database, RequestStack $request, MessengerInterface $messanger) {
    $this->account = $account;
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
      $container->get('current_user'),
    $container->get('database'),
    $container->get('request_stack'),
    $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_quiz_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = $this->database->select('quiz_type', 'u');
    $query->fields('u', ['id', 'quiz_name', 'duration', 'question_count',
      'created', 'quiz_type', 'created_time',
    ]);
    $results = $query->execute()->fetchAll();
    if (in_array('administrator', $this->account->getRoles())) {
      $form['quiz_name'] = [
        '#title' => $this->t('Quiz name'),
        '#type' => 'textfield',
      ];
      $form['quiz_type'] = [
        '#title' => $this->t('Quiz type'),
        '#type' => 'textfield',
      ];
      $form['timer'] = [
        '#title' => $this->t('Quiz time should be like in minutes 1:20 for seconds 00:30'),
        '#type' => 'textfield',
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'Add Question',
      ];
    }

    if (in_array('administrator', $this->account->getRoles())) {
      $header = [
        'quiz id' => $this->t('quiz id'),
        'quiz name' => $this->t('quiz name'),
        'duration' => $this->t('duration'),
        'question count' => $this->t('question count'),
        'created' => $this->t('created'),
        'quiz type' => $this->t('quiz type'),
        'created time' => $this->t('created time'),
        'view' => $this->t('view'),
        'delete' => $this->t('delete'),
        'edit' => $this->t('edit'),
      ];
    }
    else {
      $header = [
        'quiz id' => $this->t('quiz id'),
        'quiz name' => $this->t('quiz name'),
        'duration' => $this->t('duration'),
        'question count' => $this->t('question count'),
        'created' => $this->t('created'),
        'quiz type' => $this->t('quiz type'),
        'created time' => $this->t('created time'),
        'view' => $this->t('view'),
      ];
    }

    // $rows = [];
    if (in_array('administrator', $this->account->getRoles())) {
      $uid = $this->account->id();
      $account = User::load($uid);
      $name = $account->getAccountName();
      foreach ($results as $result) {
        if (!empty($result)) {
          $url_delete = Url::fromRoute('quizers.delete_form', ['id' => $result->quiz_name], []);
          $url_edit = Url::fromRoute('quizers.edit_form', ['id' => $result->quiz_name], []);
          $url_view = Url::fromRoute('quizers.mainform', [
            'id' => $result->quiz_name,
            'timer' => $result->duration,
            'user' => $name,
          ], []);
          $linkDelete = Link::fromTextAndUrl('Delete', $url_delete);
          $linkEdit = Link::fromTextAndUrl('Edit', $url_edit);
          $linkView = Link::fromTextAndUrl('Start', $url_view);

          $rows[] = [
            'quiz id ' => $result->id,
            'quiz name' => $result->quiz_name,
            'duration' => $result->duration,
            'question count' => $result->question_count,
            'created' => $result->created,
            'quiz type' => $result->quiz_type,
            'created time' => $result->created_time,
            'view' => $linkView,
            'delete' => $linkDelete,
            'edit' => $linkEdit,

          ];
        }
      }
    }
    else {
      foreach ($results as $result) {
        if (!empty($result)) {
          $user = $this->request->getCurrentRequest()->get('user_name');
          $url_view = Url::fromRoute('quizers.mainform', [
            'id' => $result->quiz_name,
            'timer' => $result->duration,
            'user' => $user,
          ], []);
          $linkView = Link::fromTextAndUrl('Start', $url_view);

          $rows[] = [
            'quiz id ' => $result->id,
            'quiz name' => $result->quiz_name,
            'duration' => $result->duration,
            'question count' => $result->question_count,
            'created' => $result->created,
            'quiz type' => $result->quiz_type,
            'created time' => $result->created_time,
            'view' => $linkView,
          ];
        }
      }
    }
    if (!empty($results)) {
      $form['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];
    }
    else {
      $this->messanger->addMessage($this->t("There is no Quiz found!
     Please login as admin and add quizes first."));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $quiz = $form_state->getValue('quiz_name');
    $timer = $form_state->getValue('timer');
    if (empty($quiz) || empty($timer)) {
      $form_state->setErrorByName('quiz name', $this->t('Please fill quiz name field.'));
      $form_state->setErrorByName('timer', $this->t('Please fill timer field.'));
    }
    else {
      // $database = \Drupal::database();
      $query = $this->database->select('quiz_type', 'qt');
      $query->fields('qt', ['quiz_name']);
      $query->condition('quiz_name', $quiz, '=');
      $results = $query->execute()->fetchAssoc();
      if (!empty($results)) {
        $form_state->setErrorByName('Field', $this->t('Quiz name already exist.'));
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->account->id();
    $account = User::load($uid);
    $name = $account->getAccountName();

    $this->database->insert('quiz_type')
      ->fields([
        'quiz_name' => $form_state->getValue('quiz_name'),
        'duration' => $form_state->getValue('timer'),
        'question_count' => 10,
        'quiz_type' => $form_state->getValue('quiz_type'),
        'created' => $name,
        'created_time' => date('d-m-y h:i:s'),
      ])->execute();

    $path = Url::fromRoute('quizers.add_question', ['quiz_name' => $form_state->getValue('quiz_name')])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

}
