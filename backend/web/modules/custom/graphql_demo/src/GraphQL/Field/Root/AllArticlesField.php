<?php

namespace Drupal\graphql_demo\GraphQL\Field\Root;

use Drupal\graphql_demo\GraphQL\Field\SelfAwareField;
use Drupal\graphql_demo\GraphQL\Type\ArticleType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Youshido\GraphQL\Config\Field\FieldConfig;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Field\InputField;
use Youshido\GraphQL\Type\ListType\ListType;
use Youshido\GraphQL\Type\NonNullType;
use Youshido\GraphQL\Type\Object\ObjectType;
use Youshido\GraphQL\Type\Scalar\IntType;

class AllArticlesField extends SelfAwareField implements ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function build(FieldConfig $config) {
    $config->addArgument(new InputField([
      'name' => 'offset',
      'type' => new IntType(),
    ]));

    $config->addArgument(new InputField([
      'name' => 'limit',
      'type' => new IntType(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($value, array $args, ResolveInfo $info) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $entityTypeManager->getStorage('node');

    $query = $nodeStorage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1);

    $count = (clone $query)->count()->execute();

    if (isset($args['offset']) || isset($args['limit'])) {
      $query->range(
        isset($args['offset']) ? $args['offset'] : NULL,
        isset($args['limit']) ? $args['limit'] : NULL
      );
    }

    $articles = [];
    if ($ids = $query->execute()) {
      $articles = $nodeStorage->loadMultiple($ids);
    }

    return [
      'count' => (int) $count,
      'articles' => $articles,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'allArticles';
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return new ObjectType([
      'name' => 'ArticleListType',
      'fields' => [
        'count' => [
          'type' => new NonNullType(new IntType()),
        ],
        'articles' => [
          'type' => new NonNullType(new ListType(new ArticleType())),
        ],
      ],
    ]);
  }
}