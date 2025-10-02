/**
 * Shared MCP client utilities for E2E testing
 */

import axios, { AxiosInstance } from 'axios';

export interface MCPResponse {
	content?: Array<{ type: string; text: string }>;
	structuredContent?: {
		success: boolean;
		message?: string;
		data?: any;
	};
	tools?: any[];
	error?: {
		code: number;
		message: string;
	};
}

export class MCPClient {
	private client: AxiosInstance;

	constructor(baseURL: string, username: string, password: string) {
		this.client = axios.create({
			baseURL,
			auth: { username, password },
			headers: { 'Content-Type': 'application/json' },
			timeout: 30000,
		});
	}

	async callTool(toolName: string, args: Record<string, any>): Promise<any> {
		try {
			const response = await this.client.post<MCPResponse>('', {
				jsonrpc: '2.0',
				method: 'tools/call',
				params: { name: toolName, arguments: args },
				id: 1,
			});

			if (response.data.error) {
				throw new Error(`MCP Error: ${response.data.error.message}`);
			}

			return response.data.structuredContent;
		} catch (error: any) {
			return {
				success: false,
				message: error.response?.data?.error?.message || error.message,
			};
		}
	}

	async listTools(): Promise<any[]> {
		const response = await this.client.post('', {
			jsonrpc: '2.0',
			method: 'tools/list',
			params: {},
			id: 1,
		});

		return response.data.tools || [];
	}
}

export const TEST_CONFIG = {
	baseURL: 'http://mcp.local/wp-json/mcp-adapters/v1/fluentcrm',
	username: 'admin',
	password: 'JvL0 sQrw Sis1 cKH9 7v43 Ta22',
};

export function generateTestEmail(): string {
	return `test-${Date.now()}@example.com`;
}

export function generateTestTitle(prefix: string): string {
	return `${prefix} ${Date.now()}`;
}
