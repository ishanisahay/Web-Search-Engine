package test.assignment5;

import java.io.File;
import java.io.FileInputStream;
import java.io.PrintWriter;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;


public class BigTextCreator{

public static void main(String args[]) throws Exception
{
	PrintWriter writer = new PrintWriter ("/Users/ishanisahay/Desktop/big.txt");
	String pathOfDir = "/Users/ishanisahay/solr-7.5.0/latimesfiles/latimes";
	
	int cnt = 1;
	
	File dirPtr = new File(pathOfDir);
	
	try{
			for(File file: dirPtr.listFiles())
			{
				cnt++;
				HtmlParser htmlParser = new HtmlParser();
				Metadata metaData = new Metadata();
				
				BodyContentHandler contentHandler = new BodyContentHandler(-1);
				
			
				ParseContext contextParsed = new ParseContext();
				
				FileInputStream inptStr = new FileInputStream(file);
				
				htmlParser.parse(inptStr, contentHandler, metaData, contextParsed);
				
				String fileContents = contentHandler.toString();
				
				String fileWords[] = fileContents.split(" ");
				
				for(String w: fileWords)
				{
					if(w.matches("[a-zA-Z]+\\.?"))
					{
						writer.print(w + "\n");
					}
				}
			}
		} 
	catch (Exception e) 
	{
		System.err.println("Caught IOException: " + e.getMessage());
		
		e.printStackTrace();
	}
	
	writer.close();
	
	System.out.println(cnt);
	}
}