<?php

namespace Drupal\quizers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements add quiz form.
 */
class NonAdmin extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'non_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['user_name'] = [
      '#title' => $this->t('Enter your name'),
      '#type' => 'textfield',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Go',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->getValue('user_name');
    if (empty($user)) {
      $form_state->setErrorByName('quiz name', $this->t('Please Enter user name.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $path = Url::fromRoute('quizers.addquizform', ['user_name' => $form_state->getValue('user_name')])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

}
