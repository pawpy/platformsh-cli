<?php
namespace Platformsh\Cli\Command\Integration;

use Platformsh\Cli\Command\CommandBase;
use Platformsh\Cli\Util\Table;
use Platformsh\Client\Model\Integration;
use Platformsh\ConsoleForm\Field\ArrayField;
use Platformsh\ConsoleForm\Field\BooleanField;
use Platformsh\ConsoleForm\Field\Field;
use Platformsh\ConsoleForm\Field\OptionsField;
use Platformsh\ConsoleForm\Field\UrlField;
use Platformsh\ConsoleForm\Form;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class IntegrationCommandBase extends CommandBase
{
    /** @var Form */
    private $form;

    /**
     * @return Form
     */
    protected function getForm()
    {
        if (!isset($this->form)) {
            $this->form = Form::fromArray($this->getFields());
        }

        return $this->form;
    }

    /**
     * @return Field[]
     */
    private function getFields()
    {
        return [
            'type' => new OptionsField('Type', [
                'optionName' => 'type',
                'description' => "The integration type ('github', 'hipchat', or 'webhook')",
                'options' => [
                    'github',
                    'hipchat',
                    'webhook',
                ],
            ]),
            'token' => new Field('Token', [
                'conditions' => ['type' => [
                    'github',
                    'hipchat',
                ]],
                'description' => 'GitHub or HipChat: An OAuth token for the integration',
                'validator' => function ($string) {
                    return base64_decode($string, true) !== false;
                },
            ]),
            'repository' => new Field('Repository', [
                'conditions' => ['type' => [
                    'github',
                ]],
                'description' => 'GitHub: the repository to track (the URL, e.g. \'https://github.com/user/repo\')',
                'validator' => function ($string) {
                    return substr_count($string, '/', 1) === 1;
                },
                'normalizer' => function ($string) {
                    if (preg_match('#^https?://#', $string)) {
                        return parse_url($string, PHP_URL_PATH);
                    }

                    return $string;
                },
            ]),
            'build_pull_requests' => new BooleanField('Build pull requests', [
                'conditions' => ['type' => [
                    'github',
                ]],
                'description' => 'GitHub: build pull requests as environments',
            ]),
            'fetch_branches' => new BooleanField('Fetch branches', [
                'conditions' => ['type' => [
                    'github',
                ]],
                'description' => 'GitHub: sync all branches',
            ]),
            'room' => new Field('Hipchat room ID', [
                'conditions' => ['type' => [
                    'hipchat',
                ]],
                'validator' => 'is_numeric',
                'name' => 'HipChat room ID',
            ]),
            'url' => new UrlField('URL', [
                'conditions' => ['type' => [
                    'webhook',
                ]],
                'description' => 'Generic webhook: a URL to receive JSON data',
            ]),
            'events' => new ArrayField('Events to report', [
                'conditions' => ['type' => [
                    'hipchat',
                    'webhook',
                ]],
                'default' => ['*'],
                'description' => 'Events to report, e.g. environment.push',
            ]),
            'states' => new ArrayField('States to report', [
                'conditions' => ['type' => [
                    'hipchat',
                    'webhook',
                ]],
                'name' => 'States to report',
                'default' => ['complete'],
                'description' => 'States to report, e.g. pending, in_progress, complete',
            ]),
            'environments' => new ArrayField('Environments', [
                'conditions' => ['type' => [
                    'webhook',
                ]],
                'default' => ['*'],
                'description' => 'Generic webhook: the environments relevant to the hook',
            ]),
        ];
    }

    /**
     * @param Integration $integration
     *
     * @return array
     */
    protected function formatIntegrationTable(Integration $integration)
    {
        $info = [];
        $fields = $this->getFields();
        $info['ID'] = $integration->id;
        $info['Type'] = $integration->type;
        foreach ($integration->getProperties() as $property => $value) {
            if ($property === 'id' || $property === 'type') {
                continue;
            }
            if (isset($fields[$property])) {
                $propertyName = $fields[$property]->getName();
            }
            else {
                $propertyName = ucfirst($property);
            }

            $data = is_string($value) ? $value : json_encode($value);
            $info[$propertyName] = $data;
        }
        if ($integration->hasLink('#hook')) {
            $info['Hook URL'] = $integration->getLink('#hook');
        }

        return $info;
    }

    /**
     * @param Integration     $integration
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function displayIntegration(Integration $integration, InputInterface $input, OutputInterface $output)
    {
        $table = new Table($input, $output);

        $info = [];
        $fields = $this->getFields();
        $info['ID'] = $integration->id;
        $info['Type'] = $integration->type;
        foreach ($integration->getProperties() as $property => $value) {
            if ($property === 'id' || $property === 'type') {
                continue;
            }
            if (isset($fields[$property])) {
                $propertyName = $fields[$property]->getName();
            }
            else {
                $propertyName = ucfirst($property);
            }

            $info[$propertyName] = is_string($value) ? $value : json_encode($value);
        }
        if ($integration->hasLink('#hook')) {
            $info['Hook URL'] = $integration->getLink('#hook');
        }

        if (!$table->formatIsMachineReadable()) {
            $info = array_map(function ($value) {
                return wordwrap($value, 82, "\n", true);
            }, $info);
        }

        $table->renderSimple(array_values($info), array_keys($info));
    }

}
