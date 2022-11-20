<?php

namespace Drupal\quizers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;


/**
 * Provides route responses for the Example module.
 */
class Resultpage extends ControllerBase {

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
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myPage() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $quiz = $this->request->getCurrentRequest()->get('quiz_param');
    $counter = $this->request->getCurrentRequest()->get('timer');
    $result = $this->request->getCurrentRequest()->get('results');
    $total = $result['count'] + $result['points'];
    $total_score = $result['points'] / $total * 100;
    if ($total_score >= 60) {
      $results = 'Pass';
    }
    else {
      $results = 'Fail';
    }
    $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link_back = '<a href = "' . $base_path . '/form-quizmain?id=' . $quiz . '&timer=' . $counter . '&user=' .$result['user'] . '">Retry</a>';
    $table_str = '<table>
                  <tr>
                  <td>User</td><td>' . $result['user'] . '</td></tr>
                  <tr>
                  <td>Total count</td><td>' . $total . '</td></tr>
                  <tr>
                  <td>Right Count</td><td>' . $result['points'] . '</td></tr>
                  <tr>
                  <td>Wrong Count</td><td>' . $result['count'] . '</td></tr>
                  <tr>
                  <td>Total score</td><td>' . $total_score . '%</td></tr>
                  <tr>
                  <td>Result</td><td>' . $results . '</td></tr>
                  <tr>
                  <td>' . $link_back . '</td><td></td></tr></table>';

    return [
      '#markup' => $table_str,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function mcqDelete() {
    $mcq_id = $this->request->getCurrentRequest()->get('id');
    $mcq_info = explode("/", $mcq_id);
    $this->database->delete('quiz_table')
      ->condition('id', $mcq_info[0])
      ->execute();
    $query = $this->database->select('quiz_table', 'qt');
    $query->fields('qt', ['question']);
    $query->condition('quiz_name', $mcq_info[1], '=');
    $results = $query->execute()->fetchAll();
    if (empty($results)) {
      $path = Url::fromRoute('quizers.addquizform', ['id' => $mcq_info[1]])->toString();
      $response = new RedirectResponse($path);
      $response->send();
      $this->database->delete('quiz_type')
        ->condition('quiz_name', $mcq_info[1])
        ->execute();
        $this->messanger->addMessage($this->t("deleted successfully"));
    }
    else {
      $path = Url::fromRoute('quizers.edit_form', ['id' => $mcq_info[1]])->toString();
      $response = new RedirectResponse($path);
      $response->send();
      $this->messanger->addMessage($this->t("deleted successfully"));
    }
  }

}
