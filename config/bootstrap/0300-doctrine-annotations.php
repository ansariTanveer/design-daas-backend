<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;

/*
 * Doctrine annotations are not autoloaded by default in doctrine/annotations 1.x
 */
AnnotationRegistry::registerLoader('class_exists');
