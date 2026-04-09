<?php
namespace classes;
use \SessionHandler;

class SessionDestroyHandler extends SessionHandler {
     public function destroy($sessionId): bool {
        $this->closeExternalResource($sessionId);
        return parent::destroy($sessionId);
    }

    // Chiamato casualmente da PHP per pulire le sessioni vecchie
    public function gc($maxlifetime): int|false {
        // Qui è più difficile perché PHP non ti passa il sessionId, 
        // ma elimina i FILE vecchi. 
        // Se usi i file di default, dovresti scansionare la cartella sessioni 
        // PRIMA di chiamare il parent per trovare gli ID che stanno per morire.
        return parent::gc($maxlifetime);
    }


    private function closeExternalResource($sessionId) {
        $data = new Data();
        curl_setopt($data->getCH(), CURLOPT_POSTFIELDS, json_encode([
            "cmd" => "sessions.destroy", 
            "session" => $sessionId
        ]));
        curl_exec($data->getCH());
    }
}
