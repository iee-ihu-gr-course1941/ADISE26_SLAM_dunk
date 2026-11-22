//το leaderboard παιρνει τους παικτες απο το backend (GET /api/v1/leaderboard?page=N) και τους στελνει 13-13 (paginate) σε ενα json"
//οι πληροφοριες για τις σελιδες στα -> current_page, last_page, from, prev_page_url, next_page_url
import { readResponse } from "./login.js";

const LEADERBOARD_ENDPOINT = "http://127.0.0.1:8000/api/v1/leaderboard";
const ROWS_PER_PAGE=13; //paginate(13)

let currentPage=1;
let list;
let pageNumber;
let prevButton;
let nextButton;

//για να μπορει να την καλει ο router οταν φορτωνει την σελιδα leaderboard
window.initLeaderboard = initLeaderboard;

export function initLeaderboard() {
  list=document.getElementById("leaderboard-list");
  //αν δεν ειμαστε στην σελιδα leaderboard σταματαμε
  if (!list) return;
  //αν η σελιδα εχει ηδη ξεκινησει δεν ξαναβαζουμε listeners
  if (list.dataset.ready) return;
  list.dataset.ready="true";
  pageNumber=document.getElementById("leaderboard-page-number");
  prevButton=document.getElementById("prev-page-button");
  nextButton=document.getElementById("next-page-button");
  prevButton.addEventListener("click", () => loadLeaderboardPage(currentPage - 1));
  nextButton.addEventListener("click", () => loadLeaderboardPage(currentPage + 1));
  loadLeaderboardPage(1);
}

//φερνει μια σελιδα του leaderboard απο το backend
async function loadLeaderboardPage(page) {
  try {
    const response=await fetch(`${LEADERBOARD_ENDPOINT}?page=${page}`, {
      headers: {
        Accept: "application/json",
      },
    });
    const data=await readResponse(response);
    currentPage=data.current_page;
    showPlayers(data);
    updatePagination(data);
  } catch (error) {
    console.log(error);
    showLeaderboardMessage("Could not load the leaderboard");
  }
}

//φτιαχνει παντα 13 γραμμες οπου οσες εχουν παικτη γεμιζουν και οι υπολοιπες μενουν αδειες
function showPlayers(data) {
  list.replaceChildren();
  if (data.data.length===0) {
    showLeaderboardMessage("No players yet");
    return;
  }
  
  for (let i=0; i < ROWS_PER_PAGE; i++) {
    const player=data.data[i];
    const row=document.createElement("li");
    row.className="leaderboard-row";
    if (player) {
      //το "from" ειναι η θεση του 1ου παικτη της σελιδας (π.χ. 14 στην 2η σελιδα)
      row.append(
        createCell("leaderboard-rank", "#" + (data.from + i)),
        createCell("leaderboard-username", player.username),
        createCell("leaderboard-elo", player.elo + " ELO"),
        createCell("leaderboard-record", `${player.wins}W ${player.losses}L ${player.draws}D`),
      );
    }
    list.append(row);
  }
}

//ενα κελι της γραμμης
function createCell(className, text) {
  const cell=document.createElement("span");
  cell.className=className;
  cell.textContent=text;
  return cell;
}

//γραφει "σελιδα/συνολο σελιδων" και απενεργοποιει τα βελακια οταν δεν υπαρχει αλλη σελιδα
function updatePagination(data) {
  pageNumber.textContent=`${data.current_page}/${data.last_page}`;
  prevButton.disabled=data.prev_page_url===null;
  nextButton.disabled=data.next_page_url===null;
}

//μηνυμα στην 1η γραμμη (π.χ. οταν δεν τρεχει το backend)
function showLeaderboardMessage(text) {
  list.replaceChildren();
  const row=document.createElement("li");
  row.className="leaderboard-row";
  row.append(createCell("leaderboard-message", text));
  list.append(row);
  pageNumber.textContent="";
  prevButton.disabled=true;
  nextButton.disabled=true;
}

//αν η σελιδα εχει ηδη μπει απο τον router πριν φορτωσει αυτο το αρχειο την ξεκιναμε εδω
initLeaderboard();
