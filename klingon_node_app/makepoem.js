var http=require('http');
var querystring=require('querystring');

var sys = require('sys')
var exec = require('child_process').exec;
var child;

const PORT=1337;

function processPost(request, response, callback) {
    var queryData = "";
    if(typeof callback !== 'function') return null;

    if(request.method == 'POST') {
        request.on('data', function(data) {
            queryData += data;
            if(queryData.length > 1e6) {
                queryData = "";
                response.writeHead(413, {'Content-Type': 'text/plain'}).end();
                request.connection.destroy();
            }
        });

        request.on('end', function() {
            request.post = querystring.parse(queryData);
            callback();
        });

    } else {
        response.writeHead(405, {'Content-Type': 'text/plain'});
        response.end();
    }
}




http.createServer(function(request, response) 
{
	if(request.method == 'POST') 
	{
		processPost(request, response, function() 
		{
			child = exec(request.post.cmd, function (error, stdout, stderr) 
			{
				response.writeHead(200, "OK", {'Content-Type': 'text/html'});
				response.end(stdout);
			});
		});
	} 
	else 
	{
		response.writeHead(200, "OK", {'Content-Type': 'text/plain'});
		response.end();
	}
}).listen(PORT, '127.0.0.1');
