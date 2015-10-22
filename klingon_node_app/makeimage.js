var http=require('http');
var querystring=require('querystring');
var webshot=require('webshot');
const PORT=1338;

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
			console.log(request.post);
			response.writeHead(200, "OK", {'Content-Type': 'text/html'});
			response.end();
			webshot('http://klingon-poetry.zhadum.space/preview/'+request.post.id, '/var/www/vh/klingon-poetry.zhadum.space/images/'+request.post.id+'.png', function(err){});
		});
	} 
	else 
	{
		response.writeHead(200, "OK", {'Content-Type': 'text/plain'});
		response.end();
	}
}).listen(PORT, '127.0.0.1');
