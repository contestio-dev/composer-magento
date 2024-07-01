<?php
namespace Contestio\Connect\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class SelectOptions implements ArrayInterface
{
  /**
   * Retrieve option array
   * 
   * @return array
   */
  public function toOptionArray()
  {
    return [
      ['value' => 'none', 'label' => __('Aucun')],
      ['value' => 'heart', 'label' => __('Coeur')],
      ['value' => 'heart-fill', 'label' => __('Coeur rempli')],
      ['value' => 'images', 'label' => __('Images')],
      ['value' => 'newspaper', 'label' => __('Journal')],
      ['value' => 'sparkles', 'label' => __('Paillettes')],
      ['value' => 'star-fill', 'label' => __('Étoile remplie')],
      ['value' => 'star', 'label' => __('Étoile')],
      ['value' => 'users', 'label' => __('Utilisateurs')]
    ];
  }
}

?>