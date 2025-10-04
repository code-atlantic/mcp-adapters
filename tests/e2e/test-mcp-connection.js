const axios = require('axios');

async function testConnection() {
	const client = axios.create({
		baseURL: 'http://mcp.local/wp-json/mcp-adapters/v1/fluentcrm',
		auth: {
			username: 'admin',
			password: 'JvL0 sQrw Sis1 cKH9 7v43 Ta22'
		},
		headers: { 'Content-Type': 'application/json' },
		timeout: 30000,
	});

	try {
		console.log('Testing MCP connection...');

		const response = await client.post('', {
			jsonrpc: '2.0',
			method: 'tools/call',
			params: {
				name: 'fluentcrm-create-sequence',
				arguments: { title: 'Connection Test Sequence' }
			},
			id: 1,
		});

		console.log('Response:', JSON.stringify(response.data, null, 2));

		if (response.data.structuredContent) {
			console.log('\n✅ SUCCESS! Structured content:', response.data.structuredContent);
		}

	} catch (error) {
		console.error('❌ ERROR:', error.message);
		if (error.response) {
			console.error('Response data:', error.response.data);
			console.error('Response status:', error.response.status);
		}
	}
}

testConnection();
