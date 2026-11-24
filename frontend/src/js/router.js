const route = (event) => {
  event = event || window.event;
  event.preventDefault();
  window.history.pushState({}, "", event.target.href);
  handleLocation();
};

const routes = {
  404: "/pages/404.html",
  "/": "/pages/index.html",
  "/about": "/pages/about.html",
  "/lobbies": "/pages/lobbies.html",
  "/profile": "/pages/profile.html",
  "/leaderboard": "/pages/leaderboard.html",
};

const handleLocation = async () => {
  const path = window.location.pathname;
  const route = routes[path] || routes[404];
  const html = await fetch(route).then((data) => data.text());
  document.getElementById("main-page").innerHTML = html;
  //αν η σελιδα εχει το κουμπι του IEE login το συνδεουμε με το login.js
  if (window.getLoginReady) {
    window.getLoginReady();
  }
  //οταν ανοιγει το leaderboard φερνουμε τους παικτες απο το backend
  if (path === "/leaderboard" && window.initLeaderboard) {
    window.initLeaderboard();
  }
  //Otan anoigei to profile fernoume tis plhrofories tou paikth apo to backend
  if(path === "/profile" && window.initializePlayerProfile){
    window.initializePlayerProfile();
  }
  //Otan anoigei to lobbies fernoume tis plhrofories ton lobbies apo to backend
  if(path === "/lobbies" && window.initializeLobbies){
    window.initializeLobbies();
  }
  //το fit.js ξαναπροσαρμοζει την νεα σελιδα στο παραθυρο
  if (window.fitSetup) {
    window.fitSetup();
    document
      .querySelectorAll("#main-page link[rel=stylesheet]")
      .forEach((link) => link.addEventListener("load", window.fitSetup));
  }
};

window.onpopstate = handleLocation;
window.route = route;

handleLocation();
