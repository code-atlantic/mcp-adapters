import MCPClient from "../mcp-client";

const mcp = new MCPClient("http://mcp.local/wp-json/mcp-adapters/v1/fluentcrm");

async function test() {
  const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {});
  console.log("Full result:", JSON.stringify(result, null, 2));
}

test().catch(console.error);
