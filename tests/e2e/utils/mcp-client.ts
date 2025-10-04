/**
 * Shared MCP client utilities for E2E testing
 */

import axios from "axios";
import type { AxiosInstance } from "axios";

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
      headers: { "Content-Type": "application/json" },
      timeout: 30000,
    });
  }

  async callTool(toolName: string, args: Record<string, any>): Promise<any> {
    try {
      const response = await this.client.post<MCPResponse>("", {
        jsonrpc: "2.0",
        method: "tools/call",
        params: { name: toolName, arguments: args },
        id: 1,
      });

      if (response.data.error) {
        return {
          success: false,
          message: response.data.error.message,
        };
      }

      return (
        response.data.structuredContent || {
          success: false,
          message: "No structured content in response",
        }
      );
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
      };
    }
  }

  async listTools(): Promise<any[]> {
    const response = await this.client.post("", {
      jsonrpc: "2.0",
      method: "tools/list",
      params: {},
      id: 1,
    });

    return response.data.tools || [];
  }
}

// Legacy export - use FLUENTCRM_CONFIG from test-config.ts instead
// Kept for backward compatibility with existing tests
export { FLUENTCRM_CONFIG as TEST_CONFIG } from "./test-config";

let emailCounter = 0;
export function generateTestEmail(): string {
  return `test-${Date.now()}-${emailCounter++}@example.com`;
}

let titleCounter = 0;
export function generateTestTitle(prefix: string): string {
  return `${prefix} ${Date.now()}-${titleCounter++}`;
}
