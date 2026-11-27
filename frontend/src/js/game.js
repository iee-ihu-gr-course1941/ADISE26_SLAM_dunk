import * as THREE from 'three';
import { OrbitControls } from "three/addons/controls/OrbitControls.js";

function createScene(){
  //to div mesa sto opoio tha einai to game scene mas
  //einai anejarthto apo alla divs pou tuxon na mpoune mesa sto game scene
    const canvas = document.querySelector("canvas.render");
    if(!canvas){
      return;
    }
    console.log(canvas);
  //XRHSHMA CONSTANTS EDO PANO
    const PAWNS_COLOR = {
        RED: "#C41E3A", //Red player piece color
        BLUE: "#1565C0", //Blue player piece color
    };
    
    //Gia thn dhmiourgeia mias skhnhs kanoume ena neo instance tou scene object
    const scene = new THREE.Scene();
    //Gia thn camera kanoume neo instance tou camera object
    const camera = new THREE.PerspectiveCamera(
        75,
        window.innerWidth / window.innerHeight,
        0.1,
        50,
    );
    //oi times einai FOV, oi diastaseis, to near kai to far clipping plane
    camera.position.z = 5; //to position einai idiothta tou Object3D superclass thn opoia thn klhronomoun ola ta antikeimena ths threejs
    camera.position.y = 5; //exei xrhsimes idiothtes opos x,y,z
    camera.position.x = 5; //auta mas epitrepoun na orisoume tis suntetagmenes x,y,z enos antikeimenou to opoio klhronomei thn Object3D
    camera.lookAt(0,0,0); //Xrhsimh idiothta cameras gia na thn baloume na koitaei ekei pou theloume arxika
    scene.add(camera); //epeita to camera instance mas prepei na mpei sto scene

    const controls = new OrbitControls(camera, canvas);
    controls.enableDamping = true;
    //Gia na kanoume render mia skhnh prepei na baloume kai ena renderer object
    //Parakato dhmiourgoume ena instance tou renderer object ths Three.JS
    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        antialias: true,
    });
    renderer.shadowMap.enabled = true; //ENABLE SHADOWS SOS!!!
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.render(scene, camera);

    const axesHelper = new THREE.AxesHelper(5);
    scene.add(axesHelper);

    const cubeGeometry = new THREE.BoxGeometry(1,1,1);
    const cubeMaterial = new THREE.MeshBasicMaterial({color: 'green'});
    const cube = new THREE.Mesh(cubeGeometry, cubeMaterial);
    scene.add(cube);

    const renderloop = () => {
      controls.update(); //Poly sos, an theloume na kanoume smooth ta orbit controls prepei na kanoume thn update function tous
      renderer.render(scene, camera);
      window.requestAnimationFrame(renderloop);
    }
    renderloop();
}

window.createScene = createScene;
window.addEventListener('DOMContentLoaded', () => {
  window.createScene();
});