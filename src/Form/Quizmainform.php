<?php

namespace Drupal\quizers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * {@inheritdoc}
 */
class Quizmainform extends FormBase {

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
  public function __construct(Connection $database, RequestStack $request, MessengerInterface $messanger, AccountInterface $account) {
    $this->database = $database;
    $this->request = $request;
    $this->messanger = $messanger;
    $this->account = $account;
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
      $container->get('messenger'),
    $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quizmain_form';
  }

  /**
   * Add tags field.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['countdown'] = [
      '#markup' => '<div class="countdown"></div>',
    ];
    $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $quiz_name = $this->request->getCurrentRequest()->get('id');
    $database = \Drupal::database();
    $query = $database->select('quiz_table', 'qt');
    $query->fields('qt', [
      'id', 'quiz_name', 'question', 'ans0', 'ans1', 'ans2', 'ans3', 'ans4',
    ]);
    $query->condition('quiz_name', $quiz_name, '=');
    $results = $query->execute();
    $questionair = [];
    $i = 0;
    while ($content = $results->fetchAssoc()) {
      ++$i;
      $list = [
        $content['ans0'] => $content['ans0'],
        $content['ans1'] => $content['ans1'],
        $content['ans2'] => $content['ans2'],
        $content['ans3'] => $content['ans3'],
      ];
      $keys = array_keys($list);
      shuffle($keys);
      $random = [];
      foreach ($keys as $key) {
        $random[$key] = $list[$key];
      }
      $form['question'][$content['id']] = [
        '#type' => 'radios',
        '#title' => t($i . $content['question']),
        '#options' => $random,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'submit',
    ];
    if (in_array('administrator', $this->account->getRoles())) {
      $form['back'] = [
        '#markup' => '<a href = "' . $base_path . 'add-quiz" class="markup-button">Back</a>',
      ];
    }
    $i;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $quiz_name = $this->request->getCurrentRequest()->get('id');
    $timer = $this->request->getCurrentRequest()->get('timer');
    $user = $this->request->getCurrentRequest()->get('user');

    $inputs    = $form_state->getValues();
    $id        = [];
    $ans       = [];
    $wrong_ans = [];
    $right_ans = [];
    $points    = 0;
    $total     = 0;
    foreach ($inputs as $key => $val) {
      if (is_numeric($key)) {
        $id = $key;
        $ans = $form_state->getValue($key);
        $query = $this->database->select('quiz_table', 'qt');
        $query->fields('qt', ['question', 'ans4']);
        $query->condition('id', $id, '=');
        $query->condition('ans4', $ans, '=');
        $result = $query->execute()->fetchAssoc();
        if (!empty($result)) {
          $points++;
          $right_ans[] = [
            'question' => $result['question'],
            'ans' => $ans,
          ];
        }
        else {
          $total++;
          $wrong_ans[] = [
            'ans' => $ans,
          ];
        }
      }
    }

    $path = Url::fromRoute('quizers.resultpage', [
      'quiz_param' => $quiz_name,
      'timer' => $timer, [
        'results' => [
          'count' => $total,
          'points' => $points,
          'wrongs' => $wrong_ans,
          'right_ans' => $right_ans,
          'user' => $user,
        ],
      ],
    ])->toString();
    $response = new RedirectResponse($path);
    $response->send();

  }

}
