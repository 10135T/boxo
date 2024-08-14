const WebSocket = require('ws');

const wss = new WebSocket.Server({ port: 8081 });
let clientCount = 0; // Contor pentru numărul de clienți conectați
let vector_id = [1,2];
wss.on('connection', function connection(ws) {
    // Verificăm dacă numărul de clienți este mai mic de 2
    if (clientCount < 2) {
        // Atribuim ID-ul clientului bazat pe numărul de clienți
        const clientId = vector_id[clientCount];
        clientCount++;
        console.log(clientId);
        // Trimitere ID clientului nou conectat
        ws.send(JSON.stringify({ message: 'Connected', clientId }));

        // Gestionarea mesajelor primite de la clienți
        ws.on('message', function incoming(message) {
            console.log(message);
            message = message.toString();
            // Trimitem mesajul tuturor clienților conectați
            wss.clients.forEach(function each(client) {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(message);
                }
            });
        });

        // Gestionarea deconectării clientului
        ws.on('close', function() {
            clientCount--;
        });

    } else {
        // Dacă sunt deja 2 clienți conectați, trimitem un mesaj de eroare
        ws.send(JSON.stringify({ message: 'Error', error: 'Room is full' }));
        ws.close(); // Deconectăm clientul care a încercat să se conecteze
    }
});

console.log('WebSocket server is running on ws://localhost:8081');
