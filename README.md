# Paper-io variant
This is a variant of the popular multiplayer game [paper-io](https://paper-io.com/), built as a web app using HTML, Bootstrap and JavaScript for frontend and PHP for backend. Client-server communication is done through WebSocket connection.

**!!! If only one player is connected to the server, the game pauses until there are at least two players !!!**

## My motivation
This is originally an assignment I was given in my "Web technologies" course at my university. I coded this in the spring of 2024.
It is my first experience with implementing WebSockets.

### Goal
The purpose of this assignment is to practice implementing WebSocket connection in a web app using libraries intended for this purpose.

## Installation with Docker on localhost
### Prerequisites
You need to have Docker compose installed to run this app.

### Installation
1. In your terminal, navigate to the directory inside which you wish to download this repository
2. Clone this repository using command ```git clone https://github.com/amj-j/paper-io-variant.git```
3. Navigate inside the new directory which contains the cloned repository created by the previous command.
4. If your port ```8080``` is already occupied:
  - choose a vacant port, I will demonstrate on ```8081```
  - in the ```docker-compose.yml``` file, change the port configuration on line 9 from ```8080:8080``` to ```8081:8080```
  - in the ```client/main.js``` file, change the url on line 75 from ```ws://localhost:8080``` to ```ws://localhost:8081```
5. If your port ```80``` is already occupied, in the ```docker-compose.yml``` file change the port configuration on line 21 from ```80:80``` to ```81:80``` or choose any other vacant port.
6. Execute this command to run the app: ```docker-compose up --build```. If ```docker-compose``` is unrecognized, run ```docker compose up --build```.
7. Open your browser and type ```localhost```, if you haven't changed the port ```80``` in step 5. If you have, write ```localhost:81``` (replace 81 with the port number you chose). The app will open in your browser.
8. To terminate the app, go back to your terminal and press Ctrl + C. This will kill the Docker compose process.

## Gameplay
Each player that opens the app is given a choice of name and color. After hitting the "Play" button, a rectangle of their color is generated on the screen at random position of the board.
The rectangle moves wihout player's input and cannot be stopped. Only the direction of the movement can be controlled by pressing the arrow keys on the keyboard. The player has their own territory, which has the same color as the player. The area of this territory is player's score, which means that the goal is to expand the territory as much as possible. When player leaves their territory, they leave a trace behind. When they return back into their territory, the area bounded by the trace is added to thier territory. The game cannot be won, the goal is to be the one with biggest score. The scores of players are visible in the top right corner.

You lose if:
- you hit the border of the board
- someone crosses the trace you leave behind when out of your territory
- you cross your own trace
- you press the arrow key that is the opposite to the direction you are currently moving in (because you practically cross your own trace)

