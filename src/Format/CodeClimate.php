<?php

namespace Drutiny\CodeClimate\Format;

use Drutiny\Report\Format;
use Drutiny\Report\FilesystemFormatInterface;
use Drutiny\Report\FormatInterface;
use Drutiny\Profile;
use Drutiny\AssessmentInterface;
use Llaumgui\JunitXml\JunitXmlTestSuites;
use Llaumgui\JunitXml\JunitXmlValidation;
use Symfony\Component\Console\Output\StreamOutput;
use Drutiny\Report\Twig\Helper;

/**
 * Format Drutiny profile assessments in Code Climate Engine Specification.
 */
class CodeClimate extends Format implements FilesystemFormatInterface {
  protected string $name = 'codeclimate';
  protected string $extension = 'json';
  protected string $directory;
  protected array $issues = [];

  // Map Drutiny severity to Code Climate Engine Spec severity.
  protected array $severityMap = [
    'data' => 'info',
    'low' => 'minor',
    'normal' => 'minor',
    'high' => 'major',
    'critical' => 'critical'
  ];

  // List of supported categories.
  protected array $categories = [
    'Bug Risk',
    'Clarity',
    'Compatibility',
    'Complexity',
    'Duplication',
    'Performance',
    'Security',
    'Style'
  ];

  /**
   * {@inheritdoc}
   */
  public function setWriteableDirectory(string $dir):void
  {
    $this->directory = $dir;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension():string
  {
    return $this->extension;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
      $this->twig = $this->container->get('twig');
      // Code Climate supports markdown for content rendering.
      $this->twig->addGlobal('ext', 'md');
  }

  /**
   * {@inheritdoc}
   */
  public function render(Profile $profile, AssessmentInterface $assessment):FormatInterface
  {

    foreach ($assessment->getResults() as $response) {
      $policy = $response->getPolicy();
      $tokens = $response->getTokens();

      // Following spec format. @see https://github.com/codeclimate/platform/blob/master/spec/analyzers/SPEC.md#data-types
      $this->issues[] = [
        "type" => "issue",
        "check_name" => $policy->title,
        "description" => $policy->description,
        "content" => ["body" => Helper::renderAuditReponse($this->twig, $response, $assessment)],
        "categories" => array_intersect($this->categories, $policy->tags ?? []),
        // Allow a policy or audit to define a location. Otherwise to policy UUID
        // as this field is mandatory.
        "location" => $tokens['location'] ?? $assessment->uri(),
        "severity" => in_array($response->getType(), ['notice', 'data']) ? 'info' : $this->severityMap[$response->getSeverity()],
        "fingerprint" => hash('sha256', json_encode($response->export())),
      ];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function write():iterable
  {
    $filepath = $this->directory . '/codeclimate-' . $this->namespace . '.' . $this->extension;
    $stream = new StreamOutput(fopen($filepath, 'w'));
    $stream->write(json_encode($this->issues));
    yield $filepath;
  }

}
