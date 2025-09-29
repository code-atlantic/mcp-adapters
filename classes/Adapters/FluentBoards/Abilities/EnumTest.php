<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * Simple Enum Test Tools
 */
class EnumTest extends BaseAbility {

	protected function register_abilities(): void {
		$this->register_test_verbose_enum();
		$this->register_test_pattern_enum();
		$this->register_pick_verbose_preset();
		$this->register_pick_pattern_preset();
	}

	private function register_test_verbose_enum(): void {
		wp_register_ability('test_verbose_enum', [
			'label'               => 'Test Verbose Enum',
			'description'         => 'Echo if background preset is valid (verbose enum)',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'preset' => [
						'type' => 'string',
						'enum' => [
							'solid_1', 'solid_2', 'solid_3', 'solid_4', 'solid_5',
							'solid_6', 'solid_7', 'solid_8', 'solid_9', 'solid_10',
							'solid_11', 'solid_12', 'solid_13', 'solid_14', 'solid_15',
							'solid_16', 'solid_17', 'solid_18', 'solid_19', 'solid_20',
							'solid_21', 'solid_22', 'solid_23',
							'gradient_1', 'gradient_2', 'gradient_3', 'gradient_4',
							'gradient_5', 'gradient_6'
						],
					],
				],
				'required' => [ 'preset' ],
			],
			'execute_callback'    => [ $this, 'execute_verbose' ],
			'permission_callback' => '__return_true',
		]);
	}

	private function register_test_pattern_enum(): void {
		wp_register_ability('test_pattern_enum', [
			'label'               => 'Test Pattern Enum',
			'description'         => 'Echo if background preset is valid (pattern validation)',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'preset' => [
						'type'        => 'string',
						'pattern'     => '^(solid_(?:[1-9]|1[0-9]|2[0-3])|gradient_[1-6])$',
						'description' => 'solid_1 to solid_23, or gradient_1 to gradient_6',
					],
				],
				'required' => [ 'preset' ],
			],
			'execute_callback'    => [ $this, 'execute_pattern' ],
			'permission_callback' => '__return_true',
		]);
	}

	public function execute_verbose( array $args ): array {
		$preset = $args['preset'] ?? '';
		$valid = in_array($preset, [
			'solid_1', 'solid_2', 'solid_3', 'solid_4', 'solid_5',
			'solid_6', 'solid_7', 'solid_8', 'solid_9', 'solid_10',
			'solid_11', 'solid_12', 'solid_13', 'solid_14', 'solid_15',
			'solid_16', 'solid_17', 'solid_18', 'solid_19', 'solid_20',
			'solid_21', 'solid_22', 'solid_23',
			'gradient_1', 'gradient_2', 'gradient_3', 'gradient_4',
			'gradient_5', 'gradient_6'
		], true);

		return [
			'success' => true,
			'data' => [
				'input' => $preset,
				'valid' => $valid,
				'message' => $valid ? 'VALID' : 'INVALID'
			]
		];
	}

	public function execute_pattern( array $args ): array {
		$preset = $args['preset'] ?? '';
		$valid = preg_match('/^(solid_(?:[1-9]|1[0-9]|2[0-3])|gradient_[1-6])$/', $preset) === 1;

		return [
			'success' => true,
			'data' => [
				'input' => $preset,
				'valid' => $valid,
				'message' => $valid ? 'VALID' : 'INVALID'
			]
		];
	}

	private function register_pick_verbose_preset(): void {
		wp_register_ability('generate_planet_verbose', [
			'label'               => 'Generate Planet (Verbose Enum)',
			'description'         => 'Generate a planet with a texture',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'planet_name' => [
						'type'        => 'string',
						'description' => 'Name of the planet',
					],
					'texture' => [
						'type' => 'string',
						'enum' => [
							'rock_1', 'rock_2', 'rock_3', 'rock_4', 'rock_5',
							'rock_6', 'rock_7', 'rock_8', 'rock_9', 'rock_10',
							'rock_11', 'rock_12', 'rock_13', 'rock_14', 'rock_15',
							'rock_16', 'rock_17', 'rock_18', 'rock_19', 'rock_20',
							'rock_21', 'rock_22', 'rock_23',
							'gas_1', 'gas_2', 'gas_3', 'gas_4', 'gas_5', 'gas_6'
						],
						'description' => 'Planet surface texture',
					],
				],
				'required' => [ 'planet_name', 'texture' ],
			],
			'execute_callback'    => [ $this, 'execute_generate_planet' ],
			'permission_callback' => '__return_true',
		]);
	}

	private function register_pick_pattern_preset(): void {
		wp_register_ability('generate_planet_pattern', [
			'label'               => 'Generate Planet (Pattern)',
			'description'         => 'Generate a planet with a texture',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'planet_name' => [
						'type'        => 'string',
						'description' => 'Name of the planet',
					],
					'texture' => [
						'type'        => 'string',
						'pattern'     => '^(rock_(?:[1-9]|1[0-9]|2[0-3])|gas_[1-6])$',
						'description' => 'Planet texture: rock_1 to rock_23, or gas_1 to gas_6',
					],
				],
				'required' => [ 'planet_name', 'texture' ],
			],
			'execute_callback'    => [ $this, 'execute_generate_planet' ],
			'permission_callback' => '__return_true',
		]);
	}

	public function execute_generate_planet( array $args ): array {
		$planet_name = $args['planet_name'] ?? '';
		$texture = $args['texture'] ?? '';

		// Validate texture
		$valid_textures = [
			'rock_1', 'rock_2', 'rock_3', 'rock_4', 'rock_5',
			'rock_6', 'rock_7', 'rock_8', 'rock_9', 'rock_10',
			'rock_11', 'rock_12', 'rock_13', 'rock_14', 'rock_15',
			'rock_16', 'rock_17', 'rock_18', 'rock_19', 'rock_20',
			'rock_21', 'rock_22', 'rock_23',
			'gas_1', 'gas_2', 'gas_3', 'gas_4', 'gas_5', 'gas_6'
		];

		$valid = in_array($texture, $valid_textures, true);

		return [
			'success' => true,
			'data' => $valid ? 'VALID' : 'INVALID'
		];
	}
}