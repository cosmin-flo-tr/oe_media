<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_media_iframe\Plugin\media\Source\Iframe;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for the "oe_media_iframe" widget plugin.
 *
 * @FieldWidget(
 *   id = "oe_media_iframe",
 *   label = @Translation("Media iframe"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class MediaIframeWidget extends StringTextareaWidget {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    $bundle_id = $field_definition->getTargetBundle();
    if ($bundle_id === NULL) {
      return FALSE;
    }

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle_id);

    return $media_type && $media_type->getSource() instanceof Iframe;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $media_type = $this->entityTypeManager->getStorage('media_type')->load($this->fieldDefinition->getTargetBundle());
    $text_format = $media_type->getSource()->getConfiguration()['text_format'] ?? NULL;
    $format = $this->entityTypeManager->getStorage('filter_format')->load($text_format);

    // Turn original element into a text format wrapper.
    $element['#attached']['library'][] = 'filter/drupal.filter';

    // Setup child container for the text format widget.
    $element['format'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['js-filter-wrapper']],
    ];

    // Prepare text format guidelines.
    $element['format']['guidelines'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['js-filter-guidelines']],
      '#weight' => 20,
    ];
    $element['format']['guidelines'][$format->id()] = [
      '#theme' => 'filter_guidelines',
      '#format' => $format,
    ];

    return $element;
  }

}
