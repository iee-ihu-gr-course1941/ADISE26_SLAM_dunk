const LOGOUT_ENDPOINT = "http://127.0.0.1:8000/api/v1/logout"; //συνδεουμε τα κουμπια της σελιδας με τον router και γινεται logout οταν παταμε το exit

window.initMenu = initMenu; //για να μπορει να την καλει ο router οταν φορτωνει την σελιδα menu

export function initMenu() {
  const menu = document.getElementById("main-menu");
  if (!menu) return; //αν δεν ειμαστε στην σελιδα menu σταματαμε
  if (menu.dataset.ready) return; //αν η σελιδα εχει ηδη ξεκινησει δεν ξαναβαζουμε listeners
  menu.dataset.ready = "true";
  menu.querySelectorAll("a.menu-button").forEach((link) => { //τα links (Find Lobby, Profile, Leaderboard) αλλαζουν σελιδα μεσω του router
    link.addEventListener("click", window.route);
  });
  document.getElementById("exit-button").addEventListener("click", logout);
}

//οταν παταμε exit σβηνει το token του backend (POST /api/v1/logout) και στο sessionStorage και γυρναμε στην αρχικη
async function logout() {
  const token = JSON.parse(sessionStorage.getItem("token"));
  if (token) {
    try {
      await fetch(LOGOUT_ENDPOINT, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
    } catch (error) {
      console.log(error); //αν δεν τρεχει το backend βγαινουμε παλι απο το frontend
    }
  }
  sessionStorage.removeItem("token");
  sessionStorage.removeItem("user");
  window.location.replace("/");   //replace για να μην γυρναει το back στο menu μετα το logout
}

initMenu(); //αν η σελιδα εχει ηδη μπει απο τον router πριν φορτωσει αυτο το αρχειο την ξεκιναμε εδω
