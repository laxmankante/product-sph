<?php

namespace Drupal\product_sph\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use CodeItNow\BarcodeBundle\Utils\QrCode;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Psr\Log\LoggerInterface;
use Drupal\Core\Utility\Error;

/**
 * Provides a product qr code block.
 *
 * @Block(
 *   id = "product_qr_code_block",
 *   admin_label = @Translation("Product QR Code Block"),
 * )
 */
class ProductqrcodeBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * RouteMatch used to get parameter Node.
     *
     * @var \Drupal\Core\Routing\RouteMatchInterface
     */
    protected $routeMatch;

    /**
     * Describes a logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Construct Drupal\product_sph\Plugin\Block\ProductqrcodeBlock object.
     *
     * @param array                                    $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string                                   $plugin_id
     *   The plugin_id for the plugin instance.
     * @param array                                    $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The route match.
     * @param \Psr\Log\LoggerInterface                 $logger
     *   A logger instance.
     */
    public function __construct(
      array $configuration,
      $plugin_id,
      array $plugin_definition,
      RouteMatchInterface $route_match,
      LoggerInterface $logger
    ) 
	{
      parent::__construct($configuration, $plugin_id, $plugin_definition);
      $this->routeMatch = $route_match;
      $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
      return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('current_route_match'),
        $container->get('logger.factory')->get('product_sph'),
      );
    }
    /**
     * {@inheritdoc}
     */
    public function build()
    {
      $node = $this->routeMatch->getParameter('node');

        if ($node instanceof NodeInterface) {
          if ($node->bundle() == 'products') {
            $path = $node->get('field_app_purchase_link')->getValue()[0]['uri'];
              if (!empty($path)) {
                $qrCodeImg = '';
                  if (!UrlHelper::isExternal($path)) {
                    global $base_url;
                    $path = $base_url . '' . Url::fromUri($path)->toString();
                  }
                    // Create a QR code.
                  try {
                    $qrCode = new QrCode();
                    $qrCode->setText($path)
                      ->setSize(300)
                      ->setPadding(10)
                      ->setErrorCorrection('high')
                      ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
                      ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
                      ->setLabel('Scan Qr Code')
                      ->setLabelFontSize(16)
                      ->setImageType(QrCode::IMAGE_TYPE_PNG);
					  $qrCodeImg = Markup::create('<img src="data:' . $qrCode->getContentType() . ';base64,' . $qrCode->generate() . '" />');
                    }
                    catch (Exception $Exc) {
                      $Ex = Error::decodeException($Exc);
                      $this->logger->error('%type: @message in %function (line %line of %file).', $Ex);
                    }  
                    return [
                      '#markup' => $qrCodeImg,
                      '#cache'  => ['max-age'=>0],
                    ];
                }
            }
        }
    }
}
