import * as THREE from 'three';
console.log(THREE);

const pieces = {
    "pawn-master":() => {
      const master = new THREE.Group();
      const hitboxGeometry = new THREE.ConeGeometry(0.4,2,32);
      const hitboxMaterial = new THREE.MeshBasicMaterial({
        visible: true,
        wireframe: true
      });
      const hitbox = new THREE.Mesh(hitboxGeometry,hitboxMaterial);
      master.add(hitbox);
    },
    "pawn-student":() => {

    }
};

export function createPieces(){}