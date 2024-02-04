<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use LanguageDetection\Language;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return User::all();
    }

    public function show($id)
    {
        return User::with('game')->findOrFail($id);
    }

    public function update($id, UserRequest $request)
    {
        // dd();

        if ($id != Auth::user()->id) {
            throw new Exception("Not allowed!");
        };

        $wordToCheck = $request->word;
        $game = Auth::user()->game;
        $newGame = [
            'score' => 0,
            'words' => [],
            'attempts_remaining' => 3
        ];

        $languageDetector = new Language();
        $language = $languageDetector->detect($wordToCheck)->close();
        $result = $language['en'];

        if (in_array($wordToCheck, Auth::user()->game['words']) || $result < 0.4) {
            $game['attempts_remaining']--;

            if ($game['attempts_remaining'] == 0) {
                if ($game['score'] > Auth::user()->personal_best) {
                    $newBestScore = $game['score'];
                    return User::findOrFail($id)->update(['personal_best' => $newBestScore, 'game' => $newGame]);
                };
                $game = $newGame;
            }
        } else {
            $addToScore = strlen(count_chars($wordToCheck, 3));

            function isPalindrome($word)
            {
                return (strrev($word) == $word);
            };

            if (isPalindrome($wordToCheck)) {
                $addToScore += 3;
            } else if (strlen($wordToCheck) > 2) {
                for ($i = 0; $i < strlen($wordToCheck); $i++) {
                    $wordWithoutOneChar = substr_replace($wordToCheck, '', $i, 1);
                    if (isPalindrome($wordWithoutOneChar)) {
                        $addToScore += 2;
                        break;
                    };
                }
            }

            $game['score'] += $addToScore;
            array_push($game['words'], $request->word);
        };

        return User::findOrFail($id)->update(['game' => $game]);
    }

    public function destroy($id)
    {
        return User::destroy($id);
    }
}
