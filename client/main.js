class Player {
    constructor(id, name, color, score) {
        this.id = id;
        this.name = name;
        this.color = color;
        this.nameHeading = null;
        this.element = this.createPlayerElement();
        this.updateScore(score);
        console.log("player created");
    }

    createPlayerElement() {
        const div = document.createElement('div');
        div.classList.add('container', 'd-flex', 'justify-content-between', 'mt-1', 'mb-1');
        
        this.nameHeading = document.createElement('h4');
        this.nameHeading.classList.add('m-0', 'p-0', 'border');
        this.nameHeading.textContent = this.name + ": 00";
        div.appendChild(this.nameHeading);

        const colorRect = document.createElement('div');
        colorRect.classList.add('m-0', 'p-0', 'border', 'border-3');
        colorRect.setAttribute('style', 'width: 30%;');
        colorRect.style.backgroundColor = this.color;
        div.appendChild(colorRect);

        document.getElementById("player-list").appendChild(div);
        
        return div;
    }

    updateScore(score) {
        this.nameHeading.textContent = this.name + ": " + score;
    }

    removeElement() {
        if (this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
    }
}

const grid_width = 100;
const grid_height = 50;

var canvas = document.querySelector('canvas');

canvas.width = document.getElementById('canvas-div').clientWidth;
canvas.height = (canvas.width / grid_width) * grid_height;

var canvasContext = canvas.getContext('2d');
const squareSize = Math.floor(canvas.width / grid_width);
var players = [];

var ws = null;

var formModalTarget = document.querySelector('#formModal');
var formModalInstance = null;
var endGameModalTarget = document.querySelector('#endGameModal');
var endGameModalInstance = null;

if (formModalTarget) {
    formModalInstance = new bootstrap.Modal(formModalTarget);
} else {
    console.error("Form modal target element not found.");
}

if (endGameModalTarget) {
    endGameModalInstance = new bootstrap.Modal(endGameModalTarget);
} else {
    console.error("End game modal target element not found.");
}

function connectToGame(name, color) {
    ws = new WebSocket("ws://127.0.0.1:2000");

    ws.onopen = function(e) {
        inputs = {
            name: name,
            color: color
        }
    
        ws.send(JSON.stringify(inputs));

        formModalInstance.hide();
    };
    
    ws.onmessage = function(e) {
        data = JSON.parse(e.data);
        console.log(data);

        if (data.hasOwnProperty('players') && Array.isArray(data.players) && data.hasOwnProperty('tiles') && Array.isArray(data.tiles)) {
            data.players.forEach(player => {
                players.push(new Player(player.id, player.name, player.color, player.score));
            });
            drawTiles(data.tiles);
        } else if (data.hasOwnProperty('message') && data.hasOwnProperty('score')) {
            ws.close();
            ws = null;
            canvasContext.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById("endGameModalTitle").innerText = data.message;
            document.getElementById("endGameModalScore").innerText = "Vaše skóre: " + data.score;
            endGameModalInstance.show();
        } else {
            // Save new players
            if (data.hasOwnProperty('new_players') && Array.isArray(data.new_players)) {
                for (const new_player of data.new_players) {
                    alreadyInPlayersList = false;
                    for (const player of players) {
                        if (player.id === new_player.id) {
                            alreadyInPlayersList = true;
                        }
                    }
                    if (!alreadyInPlayersList) {
                        players.push(new Player(new_player.id, new_player.name, new_player.color, new_player.score));
                    }
                }
            }
    
            // Process scores data
            if (data.hasOwnProperty('scores') && Array.isArray(data.scores)) {
                for (const el of data.scores) {
                    findPlayerById(el.id).updateScore(el.score);
                }
            }
    
            // Process tiles data
            if (data.hasOwnProperty('tiles') && Array.isArray(data.tiles)) {
                drawTiles(data.tiles);
            }

            // Delete dead players
            if (data.hasOwnProperty('dead_players_ids') && Array.isArray(data.dead_players_ids)) {
                for (const dead_player_id of data.dead_players_ids) {
                    for (i = 0; i < players.length; i++) {
                        if (players[i].id === dead_player_id) {
                            players[i].removeElement();
                            array.splice(i, 1);
                            break;
                        }
                    }
                }
            }
        }
    };
}

function findPlayerById(id) {
    for (let i = 0; i < players.length; i++) {
        if (players[i].id === id) {
            return players[i];
        }
    }
    return null;
}

function drawTiles(tiles) {
    // y axis is inverted - [0, 0] is the bottom left corner; canvas defines [0, 0] as top left corner
    tiles.forEach(tile => {
        tile.y = -(tile.y - grid_height + 1);
        canvasContext.clearRect(tile.x * squareSize, tile.y * squareSize, squareSize, squareSize);
        if (tile.residing_player_id != null) {
            let player = findPlayerById(tile.residing_player_id);
            canvasContext.fillStyle = player.color;
            canvasContext.fillRect(tile.x * squareSize, tile.y * squareSize, squareSize, squareSize);
            canvasContext.strokeStyle = 'black';
            canvasContext.strokeRect(tile.x * squareSize + 1, tile.y * squareSize + 1, squareSize - 2, squareSize - 2);
        } else if (tile.trace_owner_id != null) {
            let player = findPlayerById(tile.trace_owner_id);
            canvasContext.fillStyle = player.color + "80";
            canvasContext.fillRect(tile.x * squareSize, tile.y * squareSize, squareSize, squareSize);
        } else if (tile.owner_id != null) {
            let player = findPlayerById(tile.owner_id);
            canvasContext.fillStyle = player.color;
            canvasContext.fillRect(tile.x * squareSize, tile.y * squareSize, squareSize, squareSize);
        }
    });
}

function handleArrowKey(event) {
    if (ws != null && event.keyCode >= 37 && event.keyCode <= 40) {
        message = {};
        switch (event.key) {
            case "ArrowUp":
                message = {
                    horizontal: 0,
                    vertical: 1
                }
                break;
            case "ArrowDown":
                message = {
                    horizontal: 0,
                    vertical: -1
                }
                break;
            case "ArrowLeft":
                message = {
                    horizontal: -1,
                    vertical: 0
                }
                break;
            case "ArrowRight":
                message = {
                    horizontal: 1,
                    vertical: 0
                }
                break;
            default:
                message = {
                    horizontal: 0,
                    vertical: 0
                }
                break;
        }
        ws.send(JSON.stringify(message));
    }
}

function openFormModal() {
    endGameModalInstance.hide();
    formModalInstance.show();
    for (const player of players) {
        player.removeElement();
    }
    players = [];
}  

document.addEventListener("keydown", handleArrowKey);

document.addEventListener("DOMContentLoaded", function () {
    formModalInstance.show();
    var enterGameForm = document.getElementById("enterGameForm");
    enterGameForm.addEventListener("submit", function (event) {
        event.preventDefault();
        var name = document.getElementById("nameInput").value;
        var color = document.getElementById("colorInput").value;
        connectToGame(name, color);
    });
});