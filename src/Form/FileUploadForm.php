<?php

namespace Drupal\file_upload_redirect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

class FileUploadForm extends FormBase {

  public function getFormId() {
    return 'file_upload_redirect.upload_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = array(
      '#markup' => '<h2>Use this page to upload a PDF file.</h2>',
    );
    
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a PDF File'),
      '#upload_location' => 'public://uploads',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf', 'doc', 'docx'],
        'file_validate_size' => [25600000],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validators = $form['file']['#upload_validators'];
    $file = $form_state->getValue('file');
    
    if (empty($file)) {
      $form_state->setErrorByName('file', $this->t('The selected file could not be uploaded.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_file = $form_state->getValue('file', 0);
    
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
    }

    // Set a message for the user.
    $this->messenger()->addMessage($this->t('Your file was uploaded successfully.'));
    return;
  }
}